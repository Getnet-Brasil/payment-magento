<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Gateway\Http\Client\Pix;

use Getnet\PaymentMagento\Gateway\Config\Config;
use Getnet\PaymentMagento\Gateway\Http\Api;
use Getnet\PaymentMagento\Gateway\Http\EndpointResolver;
use Magento\Framework\Exception\LocalizedException;

/**
 * Query the current status of a payment (Global API "Payment Record Management").
 */
class BiometricStatusClient
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
     * Fetch the payment status.
     *
     * @param string   $paymentId
     * @param int|null $storeId
     *
     * @throws LocalizedException
     *
     * @return string|null Status (WAITING, APPROVED, DENIED, CANCELED...) or null when unknown
     */
    public function fetchStatus(string $paymentId, $storeId = null): ?string
    {
        if (!preg_match('/^[0-9a-f-]{36}$/i', $paymentId)) {
            return null;
        }

        $response = $this->api->sendGetRequest(
            null,
            $this->endpointResolver->resolve(EndpointResolver::PAYMENT_INFO_GET, $storeId, [$paymentId]),
            [
                'store_id'             => $storeId,
                Api::EXTRA_HEADERS     => [
                    'x-seller-id' => $this->config->getMerchantGatewaySellerId($storeId),
                ],
            ]
        );

        if (($response['payment_id'] ?? null) !== $paymentId) {
            return null;
        }

        return isset($response['status']) ? strtoupper((string) $response['status']) : null;
    }
}
