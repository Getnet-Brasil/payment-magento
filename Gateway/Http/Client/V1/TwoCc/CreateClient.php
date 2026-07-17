<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Gateway\Http\Client\V1\TwoCc;

use Getnet\PaymentMagento\Gateway\Config\Config;
use Getnet\PaymentMagento\Gateway\Http\Api;
use Getnet\PaymentMagento\Gateway\Http\EndpointResolver;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;

/**
 * Class Create Order Payment Two Cc Client - create authorization for payment by Cc.
 *
 * @SuppressWarnings(PHPCPD)
 */
class CreateClient implements ClientInterface
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
     * External Order Id - Block name.
     */
    public const EXT_ORD_ID = 'EXT_ORD_ID';

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
        $isGlobal = ($this->config->getApiType($storeId) === Config::API_TYPE_GLOBAL);

        if ($isGlobal) {
            $request = $this->convertToGlobalFormat($request, $storeId);
        }

        $responseBody = $this->api->sendPostRequest(
            $transferObject,
            $this->endpointResolver->resolve(EndpointResolver::TWO_CC_CREATE, $storeId),
            $request,
        );

        [$denied, $approvedItems] = $this->collectPaymentResults($responseBody);

        if ($isGlobal && $denied && $approvedItems) {
            // Partial approval: the order will not be placed in Magento, so the
            // approved authorizations must be reversed right away or they go orphan
            $this->rollbackApprovedPayments($transferObject, $approvedItems, $storeId);
        }

        $status = (isset($responseBody['combined_id']) && !$denied && $approvedItems) ? 1 : 0;
        $response = array_merge(
            [
                self::RESULT_CODE => $status,
                self::EXT_ORD_ID  => isset($responseBody['combined_id']) ? $responseBody['combined_id'] : null,
            ],
            $responseBody
        );

        return $response;
    }

    /**
     * Split the gateway response items into a denial flag and the approved payments.
     *
     * Mixed results come in payments[]; full denials/errors come in details[].
     *
     * @param array $responseBody
     *
     * @return array{0: int, 1: array}
     */
    private function collectPaymentResults(array $responseBody): array
    {
        $denied = 0;
        $approvedItems = [];
        $responseItems = $responseBody['payments'] ?? $responseBody['details'] ?? [];

        foreach ($responseItems as $paymentItem) {
            $itemStatus = $paymentItem['status'] ?? null;

            if (in_array($itemStatus, ['DENIED', 'ERROR'], true)) {
                $denied = 1;
            }

            if (in_array($itemStatus, ['APPROVED', 'AUTHORIZED', 'PENDING'], true)) {
                $approvedItems[] = $paymentItem;
            }
        }

        return [$denied, $approvedItems];
    }

    /**
     * Cancel the approved payments of a partially denied combined authorization.
     *
     * Best effort: a failure here is already logged by the Api client and must
     * not mask the checkout error returned to the customer.
     *
     * @param TransferInterface $transferObject
     * @param array             $approvedItems
     * @param int|null          $storeId
     *
     * @return void
     */
    public function rollbackApprovedPayments($transferObject, array $approvedItems, $storeId = null): void
    {
        $path = $this->endpointResolver->resolve(EndpointResolver::TWO_CC_CANCEL, $storeId);

        // One cancel request per payment: the endpoint does not process
        // multiple payments reliably in a single call
        foreach ($approvedItems as $item) {
            $itemKey = (string) ($item['idempotency_key'] ?? $item['payment_id'] ?? '');

            $request = [
                'store_id'   => $storeId,
                'request_id' => $this->convertToGuid('rollback-'.$itemKey),
                'payments'   => [
                    [
                        'payment_id'      => $item['payment_id'] ?? null,
                        'idempotency_key' => $itemKey.'-rollback',
                        'amount'          => isset($item['amount']) ? (int) $item['amount'] : null,
                        'payment_method'  => $item['payment_method'] ?? 'CREDIT_AUTHORIZATION',
                    ],
                ],
            ];

            try {
                $this->api->sendPostRequest($transferObject, $path, $request);
            } catch (\Exception $exc) {
                // Logged by the Api client; do not mask the original denial
                $exc->getMessage();
            }
        }
    }

    /**
     * Convert the V1 combined body to the Global API contract.
     *
     * Global wraps the payment list in a data envelope, uses payment_method
     * instead of type and takes an idempotency_key per payment.
     *
     * @param array    $request
     * @param int|null $storeId
     *
     * @return array
     */
    public function convertToGlobalFormat(array $request, $storeId = null): array
    {
        $orderId = $request['order']['order_id'] ?? null;
        $dynamicMcc = $request['dynamic_mcc'] ?? null;
        // The V1 builders set the currency per payment item, not at the root
        $currency = $request['currency'] ?? ($request['payments'][0]['currency'] ?? null);

        $payments = [];

        foreach ($request['payments'] ?? [] as $item) {
            // The TwoCc flow is always two-step (authorize + accept/capture):
            // CREDIT would be a single-step immediate capture in the Global API
            $item['payment_method'] = 'CREDIT_AUTHORIZATION';
            $item['idempotency_key'] = $item['payment_tag'] ?? null;
            unset($item['type'], $item['currency']);

            if ($dynamicMcc) {
                $item['dynamic_mcc'] = $dynamicMcc;
            }

            $payments[] = $item;
        }

        $additionalData = [];

        foreach (['customer', 'device'] as $key) {
            if (isset($request[$key])) {
                $additionalData[$key] = $request[$key];
            }
        }

        return [
            'store_id'   => $storeId,
            'request_id' => $this->convertToGuid((string) $orderId),
            'order_id'   => $orderId,
            'data'       => [
                'amount'          => $request['amount'] ?? null,
                'currency'        => $currency,
                'customer_id'     => $request['customer']['customer_id'] ?? null,
                'payments'        => $payments,
                'additional_data' => $additionalData,
            ],
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
