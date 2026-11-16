<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Gateway\Config\Config as PaymentConfig;
use Magento\Store\Model\ScopeInterface;

/**
 * Class ConfigGooglePay - Returns form of payment configuration properties.
 */
class ConfigGooglePay extends PaymentConfig
{
    /**
     * @const string
     */
    public const METHOD = 'getnet_paymentmagento_googlepay';

    /**
     * @const string
     */
    public const ACTIVE = 'active';

    /**
     * @const string
     */
    public const TITLE = 'title';

    /**
     * @const string
     */
    public const GATEWAY = 'getnetpayments';

    /**
     * Wallet id of Google Pay in the Global API.
     *
     * @const string
     */
    public const WALLET_ID = '216';

    /**
     * Wallet type of Google Pay in the Global API.
     *
     * @const string
     */
    public const WALLET_TYPE = '10';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param Config               $config
     * @param string               $methodCode
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Config $config,
        $methodCode = self::METHOD
    ) {
        parent::__construct($scopeConfig, $methodCode);
        $this->scopeConfig = $scopeConfig;
        $this->config = $config;
    }

    /**
     * Get a config value.
     *
     * Google Pay only exists in the Global API, so it is never active with api_v2 (also for the method availability).
     *
     * @param string   $field
     * @param int|null $storeId
     *
     * @return mixed
     */
    public function getValue($field, $storeId = null)
    {
        if ($field === self::ACTIVE && $this->config->getApiType($storeId) !== Config::API_TYPE_GLOBAL) {
            return false;
        }

        return parent::getValue($field, $storeId);
    }

    /**
     * Get Payment configuration status.
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function isActive($storeId = null): bool
    {
        return (bool) $this->getValue(self::ACTIVE, $storeId);
    }

    /**
     * Get title.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getTitle($storeId = null): ?string
    {
        return $this->getValue(self::TITLE, $storeId);
    }

    /**
     * Get the merchant id assigned by Getnet for the Google Pay tokenization.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getGatewayMerchantId($storeId = null): string
    {
        return trim((string) $this->getValue('gateway_merchant_id', $storeId));
    }

    /**
     * Get the Google Pay environment (TEST or PRODUCTION).
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getGoogleEnvironment($storeId = null): string
    {
        return $this->getValue('google_environment', $storeId) === 'PRODUCTION' ? 'PRODUCTION' : 'TEST';
    }

    /**
     * Get the merchant name shown in the Google Pay sheet.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getMerchantName($storeId = null): string
    {
        $name = (string) $this->getValue('merchant_name', $storeId);
        if ($name !== '') {
            return $name;
        }

        return (string) $this->scopeConfig->getValue(
            'general/store_information/name',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get the Google Pay merchant id (production only, issued by Google).
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getGoogleMerchantId($storeId = null): string
    {
        return trim((string) $this->getValue('google_merchant_id', $storeId));
    }

    /**
     * Get the button color.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getButtonColor($storeId = null): string
    {
        return $this->getValue('button_color', $storeId) === 'white' ? 'white' : 'black';
    }

    /**
     * Get the button type.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getButtonType($storeId = null): string
    {
        $type = (string) $this->getValue('button_type', $storeId);

        return in_array($type, ['buy', 'pay', 'plain', 'checkout', 'order'], true) ? $type : 'pay';
    }
}
