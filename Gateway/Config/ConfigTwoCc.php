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
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Config\Config as PaymentConfig;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Store\Model\ScopeInterface;

/**
 * Class ConfigTwoCc - Returns form of payment configuration properties.
 */
class ConfigTwoCc extends PaymentConfig
{
    /**
     * @const string
     */
    public const METHOD = 'getnet_paymentmagento_two_cc';

    /**
     * @const string
     */
    public const CC_TYPES = 'payment/getnet_paymentmagento_two_cc/cctypes';

    /**
     * @const string
     */
    public const CVV_ENABLED = 'cvv_enabled';

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
    public const CC_MAPPER = 'cctypes_mapper';

    /**
     * @const string
     */
    public const USE_GET_TAX_DOCUMENT = 'get_tax_document';

    /**
     * @const string
     */
    public const USE_GET_PHONE = 'get_phone';

    /**
     * @const string
     */
    public const USE_FRAUD_MANAGER = 'fraud_manager';

    /**
     * @const string
     */
    public const INSTALL_WITH_INTEREST = 'INSTALL_WITH_INTEREST';

    /**
     * @const string
     */
    public const INSTALL_NO_INTEREST = 'INSTALL_NO_INTEREST';

    /**
     * @const string
     */
    public const INSTALL_FULL = 'FULL';

    /**
     * @const string
     */
    public const PAYMENT_ACTION = 'payment_action';

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
}
