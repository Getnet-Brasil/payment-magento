<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Gateway\Http\Client;

use Getnet\PaymentMagento\Gateway\Config\Config;
use Getnet\PaymentMagento\Gateway\Http\Api;
use Getnet\PaymentMagento\Gateway\Http\EndpointResolver;
use Magento\Framework\Exception\LocalizedException;

/**
 * Query a payment on the Global API "Payment Record Management" (hub-payment-info).
 *
 * Used to confirm webhook notifications: the status applied to the order always comes
 * from this query, never from the notification payload.
 */
class PaymentInfoClient
{
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
     * Fetch the payment status and amount.
     *
     * @param string   $paymentId
     * @param int|null $storeId
     *
     * @throws LocalizedException
     *
     * @return array|null ['status' => string, 'amount' => int|null] or null when unknown
     */
    public function fetch(string $paymentId, $storeId = null): ?array
    {
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $paymentId)) {
            return null;
        }

        $response = $this->api->sendGetRequest(
            null,
            $this->endpointResolver->resolve(EndpointResolver::PAYMENT_INFO_GET, $storeId, [$paymentId]),
            [
                'store_id'         => $storeId,
                Api::EXTRA_HEADERS => [
                    'x-seller-id' => $this->config->getMerchantGatewaySellerId($storeId),
                ],
            ]
        );

        if (!is_array($response)
            || ($response['payment_id'] ?? null) !== $paymentId
            || empty($response['status'])
        ) {
            return null;
        }

        $amount = $response['payment']['amount'] ?? null;

        return [
            'status' => strtoupper((string) $response['status']),
            'amount' => is_numeric($amount) ? (int) $amount : null,
        ];
    }
}
