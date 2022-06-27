<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Gateway\Request;

use Getnet\PaymentMagento\Gateway\Config\Config;
use Getnet\PaymentMagento\Gateway\Config\ConfigCc;
use Getnet\PaymentMagento\Gateway\SubjectReader;
use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Model\InfoInterface;

/**
 * Class Cc Payment Data Request - Payment data structure for Credit Card.
 */
class CcPaymentDataRequest implements BuilderInterface
{
    /**
     * Credit - Block name.
     */
    public const CREDIT = 'credit';

    /**
     * Credit Delayed - Block name.
     */
    public const DELAYED = 'delayed';

    /**
     * Save Card Data - Block name.
     */
    public const TRANSACTION_TYPE = 'transaction_type';

    /**
     * Number Installment - Block name.
     */
    public const NUMBER_INSTALLMENTS = 'number_installments';

    /**
     * Statement descriptor - Invoice description.
     */
    public const SOFT_DESCRIPTOR = 'soft_descriptor';

    /**
     * Credit card - Block name.
     */
    public const CREDIT_CARD = 'card';

    /**
     * Credit Card Brand - Block Name.
     */
    public const CREDIT_CARD_BRAND = 'brand';

    /**
     * Credit card store - Block Name.
     */
    public const CREDIT_CARD_STORE = 'store';

    /**
     * Dynamic Mcc  block name.
     */
    public const DYNAMIC_MCC = 'dynamic_mcc';

    /**
     * Credit card Holder Name - Block name.
     */
    public const CREDIT_CARDHOLDER_NAME = 'cardholder_name';

    /**
     * Credit card Number Token - Block Name.
     */
    public const CREDIT_NUMBER_TOKEN = 'number_token';

    /**
     * Credit card CVV - Block Name.
     */
    public const CREDIT_CID = 'security_code';

    /**
     * Credit card Expiration Month - Block name.
     */
    public const CREDIT_MONTH = 'expiration_month';

    /**
     * Credit card Expiration Year - Block name.
     */
    public const CREDIT_YEAR = 'expiration_year';

    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ConfigCc
     */
    protected $configCc;

    /**
     * @param SubjectReader $subjectReader
     * @param Config        $config
     * @param ConfigCc      $configCc
     */
    public function __construct(
        SubjectReader $subjectReader,
        Config $config,
        ConfigCc $configCc
    ) {
        $this->subjectReader = $subjectReader;
        $this->config = $config;
        $this->configCc = $configCc;
    }

    /**
     * Build.
     *
     * @param array $buildSubject
     */
    public function build(array $buildSubject)
    {
        if (!isset($buildSubject['payment'])
            || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new InvalidArgumentException('Payment data object should be provided');
        }

        $paymentDO = $buildSubject['payment'];
        $payment = $paymentDO->getPayment();
        $order = $paymentDO->getOrder();
        $storeId = $order->getStoreId();
        $result = [];

        $result = $this->getDataPaymetCc($payment, $storeId);

        return $result;
    }

    /**
     * Data for CC.
     *
     * @param InfoInterface $payment
     * @param int           $storeId
     *
     * @return array
     */
    public function getDataPaymetCc($payment, $storeId)
    {
        $instruction = [];
        $installment = $payment->getAdditionalInformation('cc_installments') ?: 1;
        $transactionType = $this->configCc->getTypeInterestByInstallment($installment, $storeId);
        $month = $payment->getAdditionalInformation('cc_exp_month');
        if (strlen($month) === 1) {
            $month = '0'.$month;
        }
        $year = str_replace('20', '', $payment->getAdditionalInformation('cc_exp_year'));

        $instruction[self::CREDIT] = [
            self::DELAYED              => $this->configCc->hasDelayed($storeId),
            self::TRANSACTION_TYPE     => $transactionType,
            self::NUMBER_INSTALLMENTS  => $installment,
            self::SOFT_DESCRIPTOR      => $this->config->getStatementDescriptor($storeId),
            self::DYNAMIC_MCC          => $this->config->getMerchantGatewayDynamicMcc($storeId),
            self::CREDIT_CARD          => [
                self::CREDIT_NUMBER_TOKEN     => $payment->getAdditionalInformation('cc_number_token'),
                self::CREDIT_CARD_BRAND       => $payment->getAdditionalInformation('cc_type'),
                self::CREDIT_CARDHOLDER_NAME  => $payment->getAdditionalInformation('cc_cardholder_name'),
                self::CREDIT_CID              => $payment->getAdditionalInformation('cc_cid'),
                self::CREDIT_MONTH            => $month,
                self::CREDIT_YEAR             => $year,
            ],
        ];
        $payment->unsAdditionalInformation('cc_cid');

        return $instruction;
    }
}
