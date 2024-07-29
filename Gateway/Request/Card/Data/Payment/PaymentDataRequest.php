<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Gateway\Request\Card\Data\Payment;

use Getnet\PaymentMagento\Gateway\Config\Config;
use Getnet\PaymentMagento\Gateway\Config\ConfigCc;
use Getnet\PaymentMagento\Gateway\Request\Card\CardInitSchemaDataRequest;
use Getnet\PaymentMagento\Gateway\SubjectReader;
use InvalidArgumentException;
use Magento\Framework\Encryption\Encryptor;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Model\InfoInterface;

/**
 * Class Payment Data Request - Payment amount structure.
 */
class PaymentDataRequest implements BuilderInterface
{
    /**
     * Payment Block Name.
     */
    public const PAYMENT = 'payment';

    /**
     * Payment Id Block Name.
     */
    public const PAYMENT_ID = 'payment_id';

    /**
     * Payment Method Block Name.
     */
    public const PAYMENT_METHOD = 'payment_method';

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
     * @var Encryptor
     */
    protected $encrytor;

    /**
     * @param SubjectReader $subjectReader
     * @param Config        $config
     * @param ConfigCc      $configCc
     * @param Encryptor     $encrytor
     */
    public function __construct(
        SubjectReader $subjectReader,
        Config $config,
        ConfigCc $configCc,
        Encryptor $encrytor
    ) {
        $this->subjectReader = $subjectReader;
        $this->config = $config;
        $this->configCc = $configCc;
        $this->encrytor = $encrytor;
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

        $paymentDO = $this->subjectReader->readPayment($buildSubject);

        $payment = $paymentDO->getPayment();

        $result = [];

        $order = $paymentDO->getOrder();

        $storeId = $order->getStoreId();

        $installment = $payment->getAdditionalInformation('cc_installments') ?: 1;

        $transactionType = $this->configCc->getTypeInterestByInstallment($installment, $storeId);

        $method = 'CREDIT';

        if ($this->configCc->hasDelayed($storeId)) {
            $method = 'CREDIT_AUTHORIZATION';
        }

        $paymentId = $this->encrytor->hash($order->getOrderIncrementId(), 0);

        $paymentId = $this->convertToGuid($paymentId);

        $result[CardInitSchemaDataRequest::DATA][self::PAYMENT] = [
            self::PAYMENT_ID            => $paymentId,
            self::PAYMENT_METHOD        => $method,
            self::TRANSACTION_TYPE      => $transactionType,
            self::NUMBER_INSTALLMENTS   => $installment,
            self::SOFT_DESCRIPTOR       => $this->config->getStatementDescriptor($storeId),
            self::DYNAMIC_MCC           => $this->config->getMerchantGatewayDynamicMcc($storeId),
            self::CREDIT_CARD           => $this->getDataPaymetCc($payment),
        ];

        return $result;
    }

    /**
     * Convert to Guid.
     *
     * @param string $hex
     *
     * @return array
     */
    public function convertToGuid($hex) {
        if (strlen($hex) === 32 && ctype_xdigit($hex)) {
            return substr($hex, 0, 8) . '-' .
                   substr($hex, 8, 4) . '-' .
                   substr($hex, 12, 4) . '-' .
                   substr($hex, 16, 4) . '-' .
                   substr($hex, 20, 12);
        } else {
            throw new InvalidArgumentException("O valor fornecido não é um hexadecimal válido de 32 caracteres.");
        }
    }

    /**
     * Data for CC.
     *
     * @param InfoInterface $payment
     *
     * @return array
     */
    public function getDataPaymetCc($payment)
    {
        $result = [];
        $month = $payment->getAdditionalInformation('cc_exp_month');
        if (strlen($month) === 1) {
            $month = '0'.$month;
        }
        $year = str_replace('20', '', $payment->getAdditionalInformation('cc_exp_year'));

        $result = [
            self::CREDIT_NUMBER_TOKEN     => $payment->getAdditionalInformation('cc_number_token'),
            self::CREDIT_CARD_BRAND       => $payment->getAdditionalInformation('cc_type'),
            self::CREDIT_CARDHOLDER_NAME  => $payment->getAdditionalInformation('cc_cardholder_name'),
            self::CREDIT_CID              => $payment->getAdditionalInformation('cc_cid'),
            self::CREDIT_MONTH            => $month,
            self::CREDIT_YEAR             => $year,
        ];
        $payment->unsAdditionalInformation('cc_cid');

        return $result;
    }
}
