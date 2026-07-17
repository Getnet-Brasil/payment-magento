<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Gateway\Http\Client\V1\TwoCc\Operations;

use Getnet\PaymentMagento\Gateway\Config\Config;
use Getnet\PaymentMagento\Gateway\Http\Api;
use Getnet\PaymentMagento\Gateway\Http\EndpointResolver;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;

/**
 * Class Accept Payment Client - Returns authorization to accept payment.
 *
 * @SuppressWarnings(PHPCPD)
 */
class AcceptPaymentClient implements ClientInterface
{
    /**
     * Result Code - Block name.
     */
    public const RESULT_CODE = 'RESULT_CODE';

    /**
     * Store Id - Block name.
     */
    public const STORE_ID = 'store_id';

    /**
     * Response Pay Payments - Block Name.
     */
    public const RESPONSE_PAYMENTS = 'payments';

    /**
     * Response Pay Status - Block Name.
     */
    public const RESPONSE_STATUS = 'status';

    /**
     * Response Pay Status Approved - Value.
     */
    public const RESPONSE_STATUS_CONFIRMED = 'CONFIRMED';

    /**
     * Response Pay Status Denied - Value.
     */
    public const RESPONSE_STATUS_ERROR = 'ERROR';

    /**
     * @var Api
     */
    protected $api;

    /**
     * @var EndpointResolver
     */
    protected $endpointResolver;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param Api              $api
     * @param EndpointResolver $endpointResolver
     * @param Config           $config
     */
    public function __construct(
        Api $api,
        EndpointResolver $endpointResolver,
        Config $config
    ) {
        $this->api = $api;
        $this->endpointResolver = $endpointResolver;
        $this->config = $config;
    }

    /**
     * Places request to gateway.
     *
     * @param TransferInterface $transferObject
     *
     * @return array
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $request = $transferObject->getBody();
        $storeId = $request['store_id'] ?? null;
        $path = $this->endpointResolver->resolve(EndpointResolver::TWO_CC_CONFIRM, $storeId);

        if ($this->config->getApiType($storeId) === Config::API_TYPE_GLOBAL) {
            return $this->placeGlobalRequest($transferObject, $request, $path, $storeId);
        }

        $data = $this->api->sendPostRequest(
            $transferObject,
            $path,
            $request,
        );

        $response = array_merge(
            [
                self::RESULT_CODE => 0,
            ],
            $data
        );
        if (isset($data[self::RESPONSE_PAYMENTS]) &&
            $data[self::RESPONSE_PAYMENTS][0][self::RESPONSE_STATUS] === self::RESPONSE_STATUS_CONFIRMED
        ) {
            $response = array_merge(
                [
                    self::RESULT_CODE => 1,
                ],
                $data
            );
        }

        return $response;
    }

    /**
     * Capture on the Global API - one request per payment.
     *
     * The endpoint does not process multiple payments reliably in a single call.
     *
     * @param TransferInterface $transferObject
     * @param array             $request
     * @param string            $path
     * @param int|null          $storeId
     *
     * @return array
     */
    public function placeGlobalRequest($transferObject, array $request, string $path, $storeId): array
    {
        $converted = $this->convertToGlobalFormat($request, $storeId);
        $mergedItems = [];
        $allCaptured = !empty($converted['payments']);

        foreach ($converted['payments'] as $paymentItem) {
            $single = [
                'store_id'   => $storeId,
                'request_id' => $this->convertToGuid('capture-'.($paymentItem['idempotency_key'] ?? '')),
                'payments'   => [$paymentItem],
            ];

            try {
                $data = $this->api->sendPostRequest($transferObject, $path, $single);
            } catch (\Exception $exc) {
                $allCaptured = false;
                continue;
            }

            $items = $data['payments'] ?? $data['details'] ?? [];

            if (!$items) {
                $allCaptured = false;
                continue;
            }

            foreach ($items as $item) {
                $mergedItems[] = $item;
                $itemStatus = $item['status'] ?? null;

                if (!in_array($itemStatus, ['CONFIRMED', 'CAPTURED', 'APPROVED'], true)) {
                    $allCaptured = false;
                }
            }
        }

        return [
            self::RESULT_CODE       => $allCaptured ? 1 : 0,
            self::RESPONSE_PAYMENTS => $mergedItems,
        ];
    }

    /**
     * Convert the V1 payments list body to the Global API contract.
     *
     * @param array    $request
     * @param int|null $storeId
     *
     * @return array
     */
    public function convertToGlobalFormat(array $request, $storeId = null): array
    {
        $payments = [];
        $requestId = null;

        foreach ($request['payments'] ?? [] as $item) {
            $paymentTag = $item['payment_tag'] ?? '';
            $requestId = $requestId ?: preg_replace('/-\\d+$/', '', $paymentTag);
            $payments[] = [
                'payment_id'      => $item['payment_id'] ?? null,
                // the idempotency_key must be unique per operation - reusing the
                // authorization key makes the gateway reject the capture with 422
                'idempotency_key' => $paymentTag ? $paymentTag.'-capture' : null,
                'payment_method'  => 'CREDIT_AUTHORIZATION',
            ];
        }

        return [
            'store_id'   => $storeId,
            'request_id' => $this->convertToGuid((string) $requestId),
            'payments'   => $payments,
        ];
    }

    /**
     * Convert a value to a deterministic GUID (the Global API requires a GUID request_id).
     *
     * @param string $value
     *
     * @return string
     */
    public function convertToGuid(string $value): string
    {
        // phpcs:ignore Magento2.Security.InsecureFunction
        $hash = md5('getnet-combined-'.$value);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hash, 0, 8),
            substr($hash, 8, 4),
            substr($hash, 12, 4),
            substr($hash, 16, 4),
            substr($hash, 20, 12)
        );
    }
}
