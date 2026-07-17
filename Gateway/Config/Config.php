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
    public const ENDPOINT_GLOBAL_PRODUCTION = 'https://api.globalgetnet.com/';

    /**
     * @const string
     */
    public const ENDPOINT_GLOBAL_SANDBOX = 'https://api.pre.globalgetnet.com/';

    /**
     * @const string
     */
    public const API_TYPE_V2 = 'api_v2';

    /**
     * @const string
     */
    public const API_TYPE_GLOBAL = 'api_global';

    /**
     * @const string
     */
    public const AUTH_PATH_V2 = 'auth/oauth/v2/token';

    /**
     * @const string
     */
    public const AUTH_PATH_GLOBAL = 'authentication/oauth2/access_token';

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
     * Gets the API type (V2 or Global).
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getApiType($storeId = null): ?string
    {
        $apiType = $this->getAddtionalValue('api_type', $storeId);

        if ($apiType === self::API_TYPE_GLOBAL) {
            return self::API_TYPE_GLOBAL;
        }

        return self::API_TYPE_V2;
    }

    /**
     * Gets the API endpoint URL, resolved by api type and environment.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getApiUrl($storeId = null): ?string
    {
        if ($this->getApiType($storeId) === self::API_TYPE_GLOBAL) {
            if ($this->getEnvironmentMode($storeId) === self::ENVIRONMENT_HOMOLOG) {
                return self::ENDPOINT_GLOBAL_SANDBOX;
            }

            return self::ENDPOINT_GLOBAL_PRODUCTION;
        }

        return $this->getV2ApiUrl($storeId);
    }

    /**
     * Gets the API V2 endpoint URL, regardless of the configured api type.
     *
     * Used by V2-only flows (Marketplace, Boleto links) that must not switch to the Global API.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getV2ApiUrl($storeId = null): string
    {
        if ($this->getEnvironmentMode($storeId) === self::ENVIRONMENT_HOMOLOG) {
            return self::ENDPOINT_HOMOLOG;
        }

        return self::ENDPOINT_PRODUCTION;
    }

    /**
     * Gets the oAuth token path for the configured API type.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getAuthPath($storeId = null): string
    {
        if ($this->getApiType($storeId) === self::API_TYPE_GLOBAL) {
            return self::AUTH_PATH_GLOBAL;
        }

        return self::AUTH_PATH_V2;
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
     * Gets the credential config field name, resolved by api type and environment.
     *
     * @param string   $field
     * @param int|null $storeId
     *
     * @return string
     */
    public function getCredentialField(string $field, $storeId = null): string
    {
        $environment = $this->getEnvironmentMode($storeId);

        if ($this->getApiType($storeId) === self::API_TYPE_GLOBAL) {
            $suffix = ($environment === self::ENVIRONMENT_HOMOLOG) ? 'global_sandbox' : 'global_production';

            return $field.'_'.$suffix;
        }

        $suffix = ($environment === self::ENVIRONMENT_HOMOLOG) ? 'homolog' : 'production';

        return $field.'_'.$suffix;
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
        return $this->getAddtionalValue($this->getCredentialField('seller_id', $storeId), $storeId);
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
        return $this->getAddtionalValue($this->getCredentialField('client_id', $storeId), $storeId);
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
        return $this->getAddtionalValue($this->getCredentialField('client_secret', $storeId), $storeId);
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
     * Get Private Keys.
     *
     * @param int|null $storeId
     *
     * @return array
     */
    public function getPrivateKeys($storeId = null): array
    {
        return explode(',', $this->getAddtionalValue('private_keys', $storeId));
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
     * @return string|null
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
     * @param array    $request
     * @param int|null $storeId
     *
     * @return Json
     */
    public function prepareBody($request, $storeId = null)
    {
        // Global API accepts accented characters in customer data, no sanitization needed
        if ($this->getApiType($storeId) === self::API_TYPE_GLOBAL) {
            return $request;
        }

        $keysToProcess = ['first_name', 'last_name', 'name', 'street', 'district', 'complement', 'city'];

        if (is_array($request)) {
            $request = $this->removeAccentsRecursive($request, $keysToProcess);
        }

        return $request;
    }
}
