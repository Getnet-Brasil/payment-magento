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
    public const ENDPOINT_HOMOLOG = 'https://api-homologacao.getnet.com.br/';

    /**
     * @const string
     */
    public const ENVIRONMENT_HOMOLOG = 'homolog';

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
     *
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
     * @param string|int|float $amount
     *
     * @return float
     */
    public function formatPrice($amount): float
    {
        return round((float) $amount, 2) * self::ROUND_UP;
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

        if ($environment === 'homolog') {
            return self::ENDPOINT_HOMOLOG;
        }

        return self::ENDPOINT_PRODUCTION;
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

        if ($environment === 'homolog') {
            return self::ENVIRONMENT_HOMOLOG;
        }

        return self::ENVIRONMENT_PRODUCTION;
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

        if ($environment === 'homolog') {
            $sellerId = $this->getAddtionalValue('seller_id_homolog', $storeId);
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

        if ($environment === 'homolog') {
            $clientId = $this->getAddtionalValue('client_id_homolog', $storeId);
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

        if ($environment === 'homolog') {
            $clientSecret = $this->getAddtionalValue('client_secret_homolog', $storeId);
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

        if ($environment === 'homolog') {
            $oauth = $this->getAddtionalValue('access_token_homolog', $storeId);
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

        if ($environment === 'homolog') {
            $code = '1snn5n9w';
        }

        return $code;
    }

    /**
     * Use Auth In Cache.
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function useAuthInCache($storeId = null): ?bool
    {
        return (bool) $this->getAddtionalValue('use_auth_in_cache', $storeId);
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

    /**
     * Remove Accents.
     *
     * @param string $inputString
     *
     * @return string
     */
    public function removeAcents($inputString)
    {
        $filteredString = preg_replace('/[^a-zA-Z0-9áàâãéèêíìóòôõúùçñÁÀÂÃÉÈÊÍÌÓÒÔÕÚÙÇ ]/u', '', $inputString);

        return iconv('UTF-8', 'ASCII//TRANSLIT', $filteredString);
    }

    /**
     * Remove Accents Recursive.
     *
     * @param array|string $array
     * @param array        $keysToProcess
     *
     * @return array|string
     */
    public function removeAccentsRecursive($array, $keysToProcess)
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = $this->removeAccentsRecursive($value, $keysToProcess);
            } elseif (in_array($key, $keysToProcess) && is_string($value)) {
                $array[$key] = $this->removeAcents($value);
            }
        }

        return $array;
    }

    /**
     * Prepare Body.
     *
     * @param array $request
     *
     * @return Json
     */
    public function prepareBody($request)
    {
        $keysToProcess = ['first_name', 'last_name', 'name', 'street', 'district', 'complement', 'city'];

        if (is_array($request)) {
            $request = $this->removeAccentsRecursive($request, $keysToProcess);
        }

        return $this->json->serialize($request);
    }
}
