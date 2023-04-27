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
 * Class ConfigCc - Returns form of payment configuration properties.
 */
class ConfigCc extends PaymentConfig
{
    /**
     * @const string
     */
    public const METHOD = 'getnet_paymentmagento_cc';

    /**
     * @const string
     */
    public const CC_TYPES = 'payment/getnet_paymentmagento_cc/cctypes';

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
     * Should the cvv field be shown.
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function isCvvEnabled($storeId = null): bool
    {
        $pathPattern = 'payment/%s/%s';

        return (bool) $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::CVV_ENABLED),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
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
     * Get if you use document capture on the form.
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function hasUseTaxDocumentCapture($storeId = null): bool
    {
        $pathPattern = 'payment/%s/%s';

        return (bool) $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::USE_GET_TAX_DOCUMENT),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get if you use phone capture on the form.
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function hasUsePhoneCapture($storeId = null): bool
    {
        $pathPattern = 'payment/%s/%s';

        return (bool) $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::USE_GET_PHONE),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Defines whether to use FraudManager.
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function hasUseFraudManager($storeId = null): bool
    {
        $pathPattern = 'payment/%s/%s';

        return (bool) $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::USE_FRAUD_MANAGER),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Has Delayed.
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function hasDelayed($storeId = null): bool
    {
        $pathPattern = 'payment/%s/%s';
        $typePaymentAction = $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::PAYMENT_ACTION),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        if ($typePaymentAction === AbstractMethod::ACTION_AUTHORIZE_CAPTURE) {
            return false;
        }

        return true;
    }

    /**
     * Should the cc types.
     *
     * @param int|null $storeId
     *
     * @return string
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function getCcAvailableTypes($storeId = null): string
    {
        return $this->scopeConfig->getValue(
            self::CC_TYPES,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Cc Mapper.
     *
     * @param int|null $storeId
     *
     * @return array
     */
    public function getCcTypesMapper($storeId = null): array
    {
        $pathPattern = 'payment/%s/%s';

        $ccTypesMapper = $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::CC_MAPPER),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        $result = $this->json->unserialize($ccTypesMapper);

        return is_array($result) ? $result : [];
    }

    /**
     * Get info interest.
     *
     * @param int|null $storeId
     *
     * @return array
     */
    public function getInfoInterest($storeId = null): array
    {
        $interest = [];
        $interest['0'] = 0;
        $interest['1'] = -$this->scopeConfig->getValue(
            'payment/getnet_paymentmagento_cc/installment_interest_1',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        $interest['2'] = $this->scopeConfig->getValue(
            'payment/getnet_paymentmagento_cc/installment_interest_2',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        $interest['3'] = $this->scopeConfig->getValue(
            'payment/getnet_paymentmagento_cc/installment_interest_3',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        $interest['4'] = $this->scopeConfig->getValue(
            'payment/getnet_paymentmagento_cc/installment_interest_4',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        $interest['5'] = $this->scopeConfig->getValue(
            'payment/getnet_paymentmagento_cc/installment_interest_5',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        $interest['6'] = $this->scopeConfig->getValue(
            'payment/getnet_paymentmagento_cc/installment_interest_6',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        $interest['7'] = $this->scopeConfig->getValue(
            'payment/getnet_paymentmagento_cc/installment_interest_7',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        $interest['8'] = $this->scopeConfig->getValue(
            'payment/getnet_paymentmagento_cc/installment_interest_8',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        $interest['9'] = $this->scopeConfig->getValue(
            'payment/getnet_paymentmagento_cc/installment_interest_9',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        $interest['10'] = $this->scopeConfig->getValue(
            'payment/getnet_paymentmagento_cc/installment_interest_10',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        $interest['11'] = $this->scopeConfig->getValue(
            'payment/getnet_paymentmagento_cc/installment_interest_11',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        $interest['12'] = $this->scopeConfig->getValue(
            'payment/getnet_paymentmagento_cc/installment_interest_12',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $interest;
    }

    /**
     * Get min installment.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getMinInstallment($storeId = null): ?string
    {
        return $this->scopeConfig->getValue(
            'payment/getnet_paymentmagento_cc/installment_min_installment',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get max installment.
     *
     * @param int|null $storeId
     *
     * @return int|null
     */
    public function getMaxInstallment($storeId = null): ?int
    {
        return (int) $this->scopeConfig->getValue(
            'payment/getnet_paymentmagento_cc/installment_max_installment',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get type Interest by Installment.
     *
     * @param int      $installment
     * @param int|null $storeId
     *
     * @return string
     */
    public function getTypeInterestByInstallment(int $installment, $storeId = null): ?string
    {
        if ($installment === 1) {
            return self::INSTALL_FULL;
        }

        $this->getInfoInterest($storeId);
        /** A função de repassar juros depende do emissor do cartão por razão comercial a funcionalidade está desativada */
        // $interest = $this->getInfoInterest($storeId);
        // if ((int) $interest[$installment] > 0) {
        //     return self::INSTALL_WITH_INTEREST;
        // }

        return self::INSTALL_NO_INTEREST;
    }

    /**
     * Get Interest to Amount.
     *
     * @param int      $installment
     * @param float    $amount
     * @param int|null $storeId
     *
     * @return float
     */
    public function getInterestToAmount($installment, $amount, $storeId = null): float
    {
        $valueInterest = $this->getCalcInterest($installment, $amount, $storeId);

        return round($valueInterest, 2);
    }

    /**
     * Get Calculate Interest.
     *
     * @param int   $installment
     * @param float $amount
     * @param int   $storeId
     *
     * @return array
     */
    public function getCalcInterest($installment, $amount, $storeId = null): float
    {
        $interest = 0.00;
        $interestByInstall = $this->getInfoInterest($storeId);
        if ($interestByInstall[$installment] > 0) {
            $interest = $this->getInterestSimple($amount, $interestByInstall[$installment]);
        }

        return round($interest, 2);
    }

    /**
     * Interest Simple - Cc.
     *
     * @param float $amount
     * @param float $interest
     *
     * @return float
     */
    public function getInterestSimple($amount, $interest): float
    {
        $valinterest = 0.00;

        if ($interest) {
            $taxa = $interest / 100;
            $valinterest = $amount * $taxa;
        }

        return round($valinterest, 2);
    }
}
