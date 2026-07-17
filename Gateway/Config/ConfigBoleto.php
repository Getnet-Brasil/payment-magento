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
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Payment\Gateway\Config\Config as PaymentConfig;
use Magento\Store\Model\ScopeInterface;

/**
 * Class ConfigBoleto - Returns form of payment configuration properties.
 */
class ConfigBoleto extends PaymentConfig
{
    /**
     * @const string
     */
    public const METHOD = 'getnet_paymentmagento_boleto';

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
    public const INSTRUCTION_CHECKOUT = 'instruction_checkout';

    /**
     * @const string
     */
    public const EXPIRATION = 'expiration';

    /**
     * @const string
     */
    public const INSTRUCTION_LINE = 'instruction_line';

    /**
     * @const string
     */
    public const USE_GET_TAX_DOCUMENT = 'get_tax_document';

    /**
     * @const string
     */
    public const USE_GET_NAME = 'get_name';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var DateTime
     */
    protected $date;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param DateTime             $date
     * @param Config               $config
     * @param string|null          $methodCode
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        DateTime $date,
        Config $config,
        $methodCode = null
    ) {
        parent::__construct($scopeConfig, $methodCode);
        $this->scopeConfig = $scopeConfig;
        $this->date = $date;
        $this->config = $config;
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
        $pathPattern = 'payment/%s/%s';

        return (bool) $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::ACTIVE),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get title of payment.
     *
     * @param int|null $storeId
     *
     * @return string|null
     */
    public function getTitle($storeId = null): ?string
    {
        $pathPattern = 'payment/%s/%s';

        return $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::TITLE),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get Instruction - Checkoout.
     *
     * @param int|null $storeId
     *
     * @return string|null
     */
    public function getInstructionCheckout($storeId = null): ?string
    {
        $pathPattern = 'payment/%s/%s';

        return $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::INSTRUCTION_CHECKOUT),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get Expiration.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getExpiration($storeId = null): ?string
    {
        $pathPattern = 'payment/%s/%s';
        $due = $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::EXPIRATION),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $this->date->gmtDate('d/m/Y', strtotime("+{$due} days"));
    }

    /**
     * Get Expiration Formart.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getExpirationFormat($storeId = null): ?string
    {
        $pathPattern = 'payment/%s/%s';
        $due = $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::EXPIRATION),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $this->date->gmtDate('d/m/Y', strtotime("+{$due} days"));
    }

    /**
     * Get Instruction Line.
     *
     * @param int|null $storeId
     *
     * @return string|null
     */
    public function getInstructionLine($storeId = null): ?string
    {
        $pathPattern = 'payment/%s/%s';

        return $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::INSTRUCTION_LINE),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get if you use document capture on the form.
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function hasUseTaxDocumentCapture($storeId = null): ?bool
    {
        $pathPattern = 'payment/%s/%s';

        return (bool) $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::USE_GET_TAX_DOCUMENT),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get if you use name capture on the form.
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function hasUseNameCapture($storeId = null): ?bool
    {
        $pathPattern = 'payment/%s/%s';

        return (bool) $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::USE_GET_NAME),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Base path of the hub-payments service that hosts the Global boleto links.
     */
    private const GLOBAL_BOLETO_LINK_PREFIX = 'dpm/hub-payments';

    /**
     * Get Formatted Link Boleto.
     *
     * @param string   $linkRelative
     * @param int|null $storeId
     *
     * @return string
     */
    public function getFormattedLinkBoleto(string $linkRelative, $storeId = null): string
    {
        // Global API may return absolute links; return them untouched.
        if (strpos($linkRelative, 'http') === 0) {
            return $linkRelative;
        }

        $baseUrl = rtrim((string) $this->config->getApiUrl($storeId), '/');

        // On the Global API the boleto PDF href is relative to the hub-payments
        // service (e.g. "/v1/payments/boleto/{id}/pdf?ack="), not the API root.
        if ($this->config->getApiType($storeId) === Config::API_TYPE_GLOBAL) {
            $baseUrl .= '/'.self::GLOBAL_BOLETO_LINK_PREFIX;
        }

        return $baseUrl.'/'.ltrim($linkRelative, '/');
    }
}
