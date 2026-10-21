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
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Payment\Gateway\Config\Config as PaymentConfig;
use Magento\Store\Model\ScopeInterface;

/**
 * Class ConfigApplePay - Returns form of payment configuration properties.
 */
class ConfigApplePay extends PaymentConfig
{
    /**
     * @const string
     */
    public const METHOD = 'getnet_paymentmagento_applepay';

    /**
     * @const string
     */
    public const ACTIVE = 'active';

    /**
     * @const string
     */
    public const TITLE = 'title';

    /**
     * Wallet id of Apple Pay in the Global API.
     *
     * @const string
     */
    public const WALLET_ID = '103';

    /**
     * Wallet type of Apple Pay in the Global API (standard e-commerce, not SDWO).
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
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param Config               $config
     * @param EncryptorInterface   $encryptor
     * @param string               $methodCode
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Config $config,
        EncryptorInterface $encryptor,
        $methodCode = self::METHOD
    ) {
        parent::__construct($scopeConfig, $methodCode);
        $this->scopeConfig = $scopeConfig;
        $this->config = $config;
        $this->encryptor = $encryptor;
    }

    /**
     * Get a config value.
     *
     * Apple Pay only exists in the Global API, so it is never active with api_v2 (also for the method availability).
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
     * Get the Apple Merchant ID (merchant.com.your-store).
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getMerchantIdentifier($storeId = null): string
    {
        return trim((string) $this->getValue('merchant_identifier', $storeId));
    }

    /**
     * Get the name shown in the Apple Pay sheet (max 64 characters). Empty uses the store name.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getDisplayName($storeId = null): string
    {
        $name = trim((string) $this->getValue('display_name', $storeId));
        if ($name === '') {
            $name = (string) $this->scopeConfig->getValue(
                'general/store_information/name',
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
        }

        return mb_substr($name, 0, 64);
    }

    /**
     * Get the Merchant Identity Certificate (PEM) used in the merchant validation.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getCertificate($storeId = null): string
    {
        return $this->decrypt('certificate', $storeId);
    }

    /**
     * Get the private key (PEM) of the Merchant Identity Certificate.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getCertificateKey($storeId = null): string
    {
        return $this->decrypt('certificate_key', $storeId);
    }

    /**
     * Get the password of the private key (empty when the key is not protected).
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getCertificateKeyPassword($storeId = null): string
    {
        return $this->decrypt('certificate_key_password', $storeId);
    }

    /**
     * Get the button style.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getButtonStyle($storeId = null): string
    {
        $style = (string) $this->getValue('button_style', $storeId);

        return in_array($style, ['black', 'white', 'white-outline'], true) ? $style : 'black';
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

        return in_array($type, ['buy', 'pay', 'plain', 'check-out', 'order'], true) ? $type : 'pay';
    }

    /**
     * Get an encrypted config value, decrypted.
     *
     * @param string   $field
     * @param int|null $storeId
     *
     * @return string
     */
    private function decrypt(string $field, $storeId = null): string
    {
        $value = trim((string) $this->getValue($field, $storeId));

        return $value === '' ? '' : trim($this->encryptor->decrypt($value));
    }
}
