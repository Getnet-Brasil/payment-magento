<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace Getnet\PaymentMagento\Gateway\Config;

use Getnet\PaymentMagento\Gateway\Data\Order\OrderAdapterFactory;
use Getnet\PaymentMagento\Gateway\Request\AddressDataRequest;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Config\Config as PaymentConfig;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Config - Returns form of payment configuration properties.
 */
class Config extends PaymentConfig
{
    /**
     * @const string
     */
    public const METHOD = 'getnet_paymentmagento';

    /**
     * @const int
     */
    public const ROUND_UP = 100;

    /**
     * @const string
     */
    public const ENDPOINT_PRODUCTION = 'https://api.getnet.com.br/';

    /**
     * @const string
     */
    public const ENVIRONMENT_PRODUCTION = 'production';

    /**
     * @const string
     */
    public const ENDPOINT_SANDBOX = 'https://api-sandbox.getnet.com.br/';

    /**
     * @const string
     */
    public const ENVIRONMENT_SANDBOX = 'sandbox';

    /**
     * @const string
     */
    public const CLIENT = 'PaymentMagento';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Json
     */
    protected $json;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param Json                 $json
     * @param string               $methodCode
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Json $json,
        $methodCode = self::METHOD
    ) {
        parent::__construct($scopeConfig, $methodCode);
        $this->scopeConfig = $scopeConfig;
        $this->json = $json;
    }

    /**
     * Formant Price.
     *
     * @param int $amount
     *
     * @return float
     */
    public function formatPrice($amount): float
    {
        return $amount * self::ROUND_UP;
    }

    /**
     * Gets the API endpoint URL.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getApiUrl($storeId = null): ?string
    {
        $environment = $this->getEnvironmentMode($storeId);

        return $environment === 'sandbox'
            ? self::ENDPOINT_SANDBOX
            : self::ENDPOINT_PRODUCTION;
    }

    /**
     * Gets the Environment Mode.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getEnvironmentMode($storeId = null): ?string
    {
        $environment = $this->getAddtionalValue('environment', $storeId);

        return $environment === 'sandbox'
            ? self::ENVIRONMENT_SANDBOX
            : self::ENVIRONMENT_PRODUCTION;
    }

    /**
     * Gets the Merchant Gateway Seller Id.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getMerchantGatewaySellerId($storeId = null): ?string
    {
        $sellerId = $this->getAddtionalValue('seller_id_production', $storeId);

        $environment = $this->getEnvironmentMode($storeId);

        if ($environment === 'sandbox') {
            $sellerId = $this->getAddtionalValue('seller_id_sandbox', $storeId);
        }

        return $sellerId;
    }

    /**
     * Gets the Merchant Gateway Client Id.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getMerchantGatewayClientId($storeId = null): ?string
    {
        $clientId = $this->getAddtionalValue('client_id_production', $storeId);

        $environment = $this->getEnvironmentMode($storeId);

        if ($environment === 'sandbox') {
            $clientId = $this->getAddtionalValue('client_id_sandbox', $storeId);
        }

        return $clientId;
    }

    /**
     * Gets the Merchant Gateway Client Secret.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getMerchantGatewayClientSecret($storeId = null): ?string
    {
        $clientSecret = $this->getAddtionalValue('client_secret_production', $storeId);

        $environment = $this->getEnvironmentMode($storeId);

        if ($environment === 'sandbox') {
            $clientSecret = $this->getAddtionalValue('client_secret_sandbox', $storeId);
        }

        return $clientSecret;
    }

    /**
     * Gets the Merchant Gateway OAuth.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getMerchantGatewayOauth($storeId = null): ?string
    {
        $oauth = $this->getAddtionalValue('access_token_production', $storeId);

        $environment = $this->getEnvironmentMode($storeId);

        if ($environment === 'sandbox') {
            $oauth = $this->getAddtionalValue('access_token_sandbox', $storeId);
        }

        return $oauth;
    }

    /**
     * Gets the Merchant Gateway Online Metrix Code.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getMerchantGatewayOnlineMetrixCode($storeId = null): ?string
    {
        $code = 'k8vif92e';

        $environment = $this->getEnvironmentMode($storeId);

        if ($environment === 'sandbox') {
            $code = '1snn5n9w';
        }

        return $code;
    }

    /**
     * Gets the Merchant Gateway Dynamic Mcc.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getMerchantGatewayDynamicMcc($storeId = null): ?string
    {
        return $this->getAddtionalValue('dynamic_mcc', $storeId);
    }

    /**
     * Gets the AddtionalValues.
     *
     * @param string   $field
     * @param int|null $storeId
     *
     * @return string|null
     */
    public function getAddtionalValue($field, $storeId = null): ?string
    {
        $pathPattern = 'payment/%s/%s';

        return $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, $field),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get Statement Descriptor.
     *
     * @param int|null $storeId
     *
     * @return string|null
     */
    public function getStatementDescriptor($storeId = null): ?string
    {
        return $this->getAddtionalValue('statement_descriptor', $storeId);
    }

    /**
     * Get Address Limit to Send.
     *
     * @param string $field
     *
     * @return int $limitSend
     */
    public function getAddressLimitSend($field): int
    {
        $limitSend = 57;
        if ($field === AddressDataRequest::STREET) {
            $limitSend = 57;
        } elseif ($field === AddressDataRequest::NUMBER) {
            $limitSend = 6;
        } elseif ($field === AddressDataRequest::DISTRICT) {
            $limitSend = 60;
        } elseif ($field === AddressDataRequest::COMPLEMENT) {
            $limitSend = 30;
        }

        return $limitSend;
    }

    /**
     * Value For Field Address.
     *
     * @param OrderAdapterFactory $adress
     * @param string              $field
     *
     * @return string|null
     */
    public function getValueForAddress($adress, $field): ?string
    {
        $value = (int) $this->getAddtionalValue($field);
        $limitSend = $this->getAddressLimitSend($field);

        if ($value === 0) {
            return substr($adress->getStreetLine1(), 0, $limitSend);
        } elseif ($value === 1) {
            return substr($adress->getStreetLine2(), 0, $limitSend);
        } elseif ($value === 2) {
            if ($adress->getStreetLine3()) {
                return substr($adress->getStreetLine3(), 0, $limitSend);
            }
        } elseif ($value === 3) {
            if ($adress->getStreetLine4()) {
                return substr($adress->getStreetLine4(), 0, $limitSend);
            }
        }

        if ($field === AddressDataRequest::DISTRICT) {
            return substr($adress->getStreetLine1(), 0, $limitSend);
        }

        return '';
    }
}
