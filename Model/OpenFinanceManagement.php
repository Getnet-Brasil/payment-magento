<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Model;

use Getnet\PaymentMagento\Gateway\Config\Config;
use Getnet\PaymentMagento\Gateway\Config\ConfigPix;
use Getnet\PaymentMagento\Gateway\Http\Api;
use Getnet\PaymentMagento\Gateway\Http\EndpointResolver;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Open Finance merchant setup used by Pix with biometrics (Global API).
 *
 * The redirect_uri is configured per seller (not per order): the payer is sent back to it
 * at the end of the biometric journey. The API answers 204 with no body and has no GET,
 * so the current value cannot be read back.
 */
class OpenFinanceManagement
{
    public const OPERATION_DOMAIN = 'domain';

    /**
     * @var ApiManagement
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
     * @var ConfigPix
     */
    protected $configPix;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param ApiManagement         $api
     * @param EndpointResolver      $endpointResolver
     * @param Config                $config
     * @param ConfigPix             $configPix
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ApiManagement $api,
        EndpointResolver $endpointResolver,
        Config $config,
        ConfigPix $configPix,
        StoreManagerInterface $storeManager
    ) {
        $this->api = $api;
        $this->endpointResolver = $endpointResolver;
        $this->config = $config;
        $this->configPix = $configPix;
        $this->storeManager = $storeManager;
    }

    /**
     * Return url the payer lands on after the biometric journey.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getReturnUrl($storeId = null): string
    {
        $store = $this->storeManager->getStore($storeId);

        return $this->configPix->getBiometricReturnUrl((int) $store->getId())
            ?: $store->getBaseUrl(UrlInterface::URL_TYPE_LINK, true).ConfigPix::BIOMETRIC_RETURN_ROUTE;
    }

    /**
     * Register the redirect_uri of the seller.
     *
     * @param int|null    $storeId
     * @param string|null $redirectUri Explicit uri; defaults to getReturnUrl()
     *
     * @throws LocalizedException
     *
     * @return array ['success' => bool, 'message' => string, 'redirect_uri' => string]
     */
    public function registerDomain($storeId = null, ?string $redirectUri = null): array
    {
        if ($this->config->getApiType($storeId) !== Config::API_TYPE_GLOBAL) {
            throw new LocalizedException(
                __('Pix by biometrics is available only in the API Global.')
            );
        }

        $store = $this->storeManager->getStore($storeId);
        $redirectUri = $redirectUri ?: $this->getReturnUrl((int) $store->getId());

        if (!filter_var($redirectUri, FILTER_VALIDATE_URL) || strpos($redirectUri, 'https://') !== 0) {
            throw new LocalizedException(__('Invalid return URL. Use an https URL.'));
        }

        try {
            $response = $this->api->sendPostRequest(
                $this->endpointResolver->resolve(EndpointResolver::OPENFINANCE_MERCHANT, $store->getId()),
                [
                    'store_id'         => (int) $store->getId(),
                    Api::EXTRA_HEADERS => [
                        'x-seller-id' => $this->config->getMerchantGatewaySellerId($store->getId()),
                    ],
                    'operation'        => self::OPERATION_DOMAIN,
                    'payload'          => [
                        'redirect_uri' => $redirectUri,
                    ],
                ]
            );
        } catch (\Exception $exc) {
            return [
                'success'      => false,
                'message'      => $exc->getMessage(),
                'redirect_uri' => $redirectUri,
            ];
        }

        $success = (int) ($response['http_status'] ?? 0) === 204;

        return [
            'success'      => $success,
            'message'      => $success
                ? (string) __('Return URL registered.')
                : (string) ($response['message'] ?? __('Unexpected gateway response.')),
            'redirect_uri' => $redirectUri,
        ];
    }
}
