<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Gateway\Request\V1\TwoCc\Operations;

use Getnet\PaymentMagento\Gateway\Config\Config;
use Getnet\PaymentMagento\Gateway\Config\ConfigCc;
use Getnet\PaymentMagento\Gateway\SubjectReader;
use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Model\InfoInterface;

/**
 * Class Two Cc Payment Data Request - Payment data structure for Credit Card.
 */
class TwoCcPaymentDataRequest implements BuilderInterface
{
    /**
     * Payments - Block name.
     */
    public const PAYMENTS = 'payments';

    /**
     * Type - Block name.
     */
    public const TYPE = 'type';

    /**
     * Amount - Block name.
     */
    public const AMOUNT = 'amount';

    /**
     * Currency - Block name.
     */
    public const CURRENCY = 'currency';

    /**
     * Credit - Block name.
     */
    public const CREDIT = 'CREDIT';

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
     * Payment Tag - Invoice description.
     */
    public const PAYMENT_TAG = 'payment_tag';

    /**
     * Credit card - Block name.
     */
    public const CREDIT_CARD = 'card';

    /**
     * Credit card store - Block Name.
     */
    public const CREDIT_CARD_STORE = 'store';

    /**
     * Credit card Holder Name - Block name.
     */
    public const CREDIT_CARDHOLDER_NAME = 'cardholder_name';

    /**
     * Credit Card Brand - Block Name.
     */
    public const CREDIT_CARD_BRAND = 'brand';

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
     * Save Card Data - Block name.
     */
    public const SAVE_CARD_DATA = 'save_card_data';

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
        $incrementId = $order->getOrderIncrementId();
        $currency = $order->getCurrencyCode();
        $result = [];

        $result[self::PAYMENTS][] = $this->getDataPaymentCc($payment, $incrementId, $currency, $storeId);
        $result[self::PAYMENTS][] = $this->getDataSecondaryPaymentCc($payment, $incrementId, $currency, $storeId);

        return $result;
    }

    /**
     * Data for CC.
     *
     * @param InfoInterface $payment
     * @param string        $incrementId
     * @param string        $currency
     * @param int           $storeId
     *
     * @return array
     */
    public function getDataPaymentCc($payment, $incrementId, $currency, $storeId)
    {
        $payments = [];

        $totalFirst = $payment->getAdditionalInformation('cc_payment_first_amount');
        $installment = $payment->getAdditionalInformation('cc_installments') ?: 1;
        $transactionType = $this->configCc->getTypeInterestByInstallment($installment, $storeId);
        $month = $payment->getAdditionalInformation('cc_exp_month');
        if (strlen($month) === 1) {
            $month = '0'.$month;
        }
        $year = str_replace('20', '', $payment->getAdditionalInformation('cc_exp_year'));

        $payments = [
            self::TYPE                 => self::CREDIT,
            self::AMOUNT               => $this->config->formatPrice($totalFirst),
            self::CURRENCY             => $currency,
            self::SAVE_CARD_DATA       => false,
            self::TRANSACTION_TYPE     => $transactionType,
            self::NUMBER_INSTALLMENTS  => $installment,
            self::SOFT_DESCRIPTOR      => $this->config->getStatementDescriptor($storeId),
            self::PAYMENT_TAG          => $incrementId.'-1',
            self::CREDIT_CARD          => [
                self::CREDIT_CARD_BRAND       => $payment->getAdditionalInformation('cc_type'),
                self::CREDIT_NUMBER_TOKEN     => $payment->getAdditionalInformation('cc_number_token'),
                self::CREDIT_CARDHOLDER_NAME  => $payment->getAdditionalInformation('cc_cardholder_name'),
                self::CREDIT_CID              => $payment->getAdditionalInformation('cc_cid'),
                self::CREDIT_MONTH            => $month,
                self::CREDIT_YEAR             => $year,
            ],
        ];
        $payment->unsAdditionalInformation('cc_cid');

        return $payments;
    }

    /**
     * Data for Secondary CC.
     *
     * @param InfoInterface $payment
     * @param string        $incrementId
     * @param string        $currency
     * @param int           $storeId
     *
     * @return array
     */
    public function getDataSecondaryPaymentCc(
        $payment,
        $incrementId,
        $currency,
        $storeId
    ) {
        $payments = [];
        $totalSecondary = $payment->getAdditionalInformation('cc_payment_secondary_amount');
        $installmentSecondary = $payment->getAdditionalInformation('cc_secondary_installments') ?: 1;
        $transTypeSecondary = $this->configCc->getTypeInterestByInstallment($installmentSecondary, $storeId);
        $monthSecondary = $payment->getAdditionalInformation('cc_secondary_exp_month');
        if (strlen($monthSecondary) === 1) {
            $monthSecondary = '0'.$monthSecondary;
        }
        $yearSecondary = str_replace('20', '', $payment->getAdditionalInformation('cc_secondary_exp_year'));

        $payments = [
            self::TYPE                 => self::CREDIT,
            self::AMOUNT               => $this->config->formatPrice($totalSecondary),
            self::CURRENCY             => $currency,
            self::SAVE_CARD_DATA       => false,
            self::TRANSACTION_TYPE     => $transTypeSecondary,
            self::NUMBER_INSTALLMENTS  => $installmentSecondary,
            self::SOFT_DESCRIPTOR      => $this->config->getStatementDescriptor($storeId),
            self::PAYMENT_TAG          => $incrementId.'-2',
            self::CREDIT_CARD          => [
                self::CREDIT_CARD_BRAND       => $payment->getAdditionalInformation('cc_secondary_type'),
                self::CREDIT_NUMBER_TOKEN     => $payment->getAdditionalInformation('cc_secondary_number_token'),
                self::CREDIT_CARDHOLDER_NAME  => $payment->getAdditionalInformation('cc_secondary_cardholder_name'),
                self::CREDIT_CID              => $payment->getAdditionalInformation('cc_secondary_cid'),
                self::CREDIT_MONTH            => $monthSecondary,
                self::CREDIT_YEAR             => $yearSecondary,
            ],
        ];

        $payment->unsAdditionalInformation('cc_secondary_cid');

        return $payments;
    }
}
