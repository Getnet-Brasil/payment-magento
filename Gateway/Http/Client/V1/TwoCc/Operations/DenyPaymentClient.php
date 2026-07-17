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
 * Class Deny Payment Client - Returns authorization to denied payment.
 *
 * @SuppressWarnings(PHPCPD)
 */
class DenyPaymentClient implements ClientInterface
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
     * Day Zero block name.
     */
    public const DAY_ZERO = 'day_zero';

    /**
     * Response Pay Payments - Block Name.
     */
    public const RESPONSE_PAYMENTS = 'payments';

    /**
     * Response Pay Status - Block Name.
     */
    public const RESPONSE_STATUS = 'status';

    /**
     * Response Pay Status Denied - Value.
     */
    public const RESPONSE_STATUS_DENIED = 'DENIED';

    /**
     * Response Pay Cancel Request Id - Block name.
     */
    public const RESPONSE_CANCEL_REQUEST_ID = 'cancel_request_id';

    /**
     * Amount block name.
     */
    public const CANCEL_AMOUNT = 'cancel_amount';

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
        $context = [];
        $paymentId = $request['payment_id'];
        $storeId = $request['store_id'] ?? null;
        $path = $this->endpointResolver->resolve(EndpointResolver::TWO_CC_CANCEL_REQUEST, $storeId);

        if ($request[self::DAY_ZERO]) {
            $path = $this->endpointResolver->resolve(EndpointResolver::TWO_CC_CANCEL, $storeId);
        }

        if ($this->config->getApiType($storeId) === Config::API_TYPE_GLOBAL) {
            return $this->placeGlobalRequest($transferObject, $request, $storeId);
        }

        unset($request['payment_id']);
        unset($request[self::CANCEL_AMOUNT]);
        unset($request[self::DAY_ZERO]);

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

        if (isset($data[self::RESPONSE_PAYMENTS])) {
            $context = $data[self::RESPONSE_PAYMENTS][0];
        }

        if (isset($data[self::RESPONSE_PAYMENTS][0])) {
            $response = array_merge(
                [
                    self::RESULT_CODE                 => 1,
                    self::RESPONSE_CANCEL_REQUEST_ID  => $context[self::RESPONSE_CANCEL_REQUEST_ID] ?? null,
                ],
                $data
            );
            if (($context[self::RESPONSE_STATUS] ?? null) === self::RESPONSE_STATUS_DENIED) {
                $response = array_merge(
                    [
                        self::RESULT_CODE                 => 0,
                        self::RESPONSE_CANCEL_REQUEST_ID  => $context[self::RESPONSE_CANCEL_REQUEST_ID] ?? null,
                    ],
                    $data
                );
            }
        }

        if (isset($context[self::RESPONSE_STATUS])) {
            if ($context[self::RESPONSE_STATUS] === 'CANCELED') {
                $response = array_merge(
                    [
                        self::RESULT_CODE                 => 1,
                        self::RESPONSE_CANCEL_REQUEST_ID  => $paymentId.'-cancel',
                    ],
                    $data
                );
            }
        }

        return $response;
    }

    /**
     * Cancel on the Global API - one request per card payment.
     *
     * The combined_id is not accepted by the cancel endpoint and multiple
     * payments in a single call are not processed reliably: each card
     * payment_id (payments[] from DataForTwoCcRequest) is canceled individually.
     *
     * @param TransferInterface $transferObject
     * @param array             $request
     * @param int|null          $storeId
     *
     * @return array
     */
    public function placeGlobalRequest($transferObject, array $request, $storeId): array
    {
        $path = $this->endpointResolver->resolve(EndpointResolver::TWO_CC_CANCEL, $storeId);
        $items = $request['payments'] ?? [];

        if (!$items && isset($request['payment_id'])) {
            $items = [
                [
                    'payment_id'  => $request['payment_id'],
                    'payment_tag' => (string) ($request['idempotency_key'] ?? ''),
                ],
            ];
        }

        $mergedItems = [];
        $allCanceled = !empty($items);

        foreach ($items as $item) {
            $tag = (string) ($item['payment_tag'] ?? $item['payment_id'] ?? '');
            $single = [
                'store_id'   => $storeId,
                'request_id' => $this->convertToGuid('cancel-'.$tag),
                'payments'   => [
                    [
                        'payment_id'      => $item['payment_id'] ?? null,
                        'idempotency_key' => $tag.'-void',
                        'payment_method'  => 'CREDIT_AUTHORIZATION',
                    ],
                ],
            ];

            try {
                $data = $this->api->sendPostRequest($transferObject, $path, $single);
            } catch (\Exception $exc) {
                $allCanceled = false;
                continue;
            }

            $responseItems = $data['payments'] ?? $data['details'] ?? [];

            if (!$responseItems) {
                $allCanceled = false;
                continue;
            }

            foreach ($responseItems as $responseItem) {
                $mergedItems[] = $responseItem;

                if (($responseItem['status'] ?? null) !== 'CANCELED') {
                    $allCanceled = false;
                }
            }
        }

        return [
            self::RESULT_CODE                => $allCanceled ? 1 : 0,
            'payment_id'                     => $request['payment_id'] ?? null,
            self::RESPONSE_PAYMENTS          => $mergedItems,
            self::RESPONSE_CANCEL_REQUEST_ID => ($request['payment_id'] ?? '').'-cancel',
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
