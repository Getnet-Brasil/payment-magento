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
use Getnet\PaymentMagento\Gateway\Config\ConfigWallet;
use Getnet\PaymentMagento\Gateway\SubjectReader;
use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Model\InfoInterface;

/**
 * Class Wallet Payment Data Request - Payment data structure for Credit Card.
 */
class WalletPaymentDataRequest implements BuilderInterface
{
    /**
     * Payment - Block name.
     */
    public const PAYMENTS = 'payments';

    /**
     * Type - Block name.
     */
    public const TYPE = 'type';
    
    /**
     * Credit - Block name.
     */
    public const CREDIT = 'credit';
  
    /**
     * Transaction Type - Block name.
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
     * Credit Holder Phone - Block name.
     */
    public const CARDHOLDER_MOBILE = 'cardholder_mobile';

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
     * @var ConfigWallet
     */
    protected $configWallet;

    /**
     * @param SubjectReader $subjectReader
     * @param Config        $config
     * @param ConfigCc      $configCc
     * @param ConfigWallet  $configWallet
     */
    public function __construct(
        SubjectReader $subjectReader,
        Config $config,
        ConfigCc $configCc,
        ConfigWallet $configWallet
    ) {
        $this->subjectReader = $subjectReader;
        $this->config = $config;
        $this->configCc = $configCc;
        $this->configWallet = $configWallet;
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

        $result = $this->getDataPaymetWallet($payment, $storeId);

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
    public function getDataPaymetWallet($payment, $storeId)
    {
        $instruction = [];
        $cardType = $payment->getAdditionalInformation('wallet_card_type');
        $phone = $payment->getAdditionalInformation('wallet_payer_phone');

        if ($cardType === 'credit') {
            $installment = $payment->getAdditionalInformation('cc_installments') ?: 1;
            $transactionType = $this->configCc->getTypeInterestByInstallment($installment, $storeId);

            $instruction[self::PAYMENTS][] = [
                self::TYPE                 => 'CREDIT',
                self::SOFT_DESCRIPTOR      => $this->config->getStatementDescriptor($storeId),
                self::TRANSACTION_TYPE     => $transactionType,
                self::NUMBER_INSTALLMENTS  => $installment,
                self::CARDHOLDER_MOBILE    => preg_replace('/[^0-9]/', '', $phone),
            ];
            return $instruction;
        }

        if ($cardType === 'debit') {
            $instruction[self::PAYMENTS][] = [
                self::TYPE                 => 'DEBIT',
                self::SOFT_DESCRIPTOR      => $this->config->getStatementDescriptor($storeId),
                self::CARDHOLDER_MOBILE    => preg_replace('/[^0-9]/', '', $phone),
            ];
            return $instruction;
        }

        return $instruction;
    }
}
