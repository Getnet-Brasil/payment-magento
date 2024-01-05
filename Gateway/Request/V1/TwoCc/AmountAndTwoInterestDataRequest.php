<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Gateway\Request\V1\TwoCc;

use Getnet\PaymentMagento\Gateway\Config\Config;
use Getnet\PaymentMagento\Gateway\Config\ConfigCc;
use Getnet\PaymentMagento\Gateway\Data\Order\OrderAdapterFactory;
use Getnet\PaymentMagento\Gateway\SubjectReader;
use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;

/**
 * Class Amount And Two Interest Data Request - Payment amount structure.
 */
class AmountAndTwoInterestDataRequest implements BuilderInterface
{
    /**
     * Amount block name.
     */
    public const AMOUNT = 'amount';

    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * @var OrderAdapterFactory
     */
    protected $orderAdapterFactory;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ConfigCc
     */
    protected $configCc;

    /**
     * @param SubjectReader       $subjectReader
     * @param OrderAdapterFactory $orderAdapterFactory
     * @param Config              $config
     * @param ConfigCc            $configCc
     */
    public function __construct(
        SubjectReader $subjectReader,
        OrderAdapterFactory $orderAdapterFactory,
        Config $config,
        ConfigCc $configCc
    ) {
        $this->subjectReader = $subjectReader;
        $this->orderAdapterFactory = $orderAdapterFactory;
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

        $paymentDO = $this->subjectReader->readPayment($buildSubject);

        $result = [];

        $order = $paymentDO->getOrder();

        $grandTotal = $order->getGrandTotalAmount();

        $payment = $paymentDO->getPayment();

        $installment = $payment->getAdditionalInformation('cc_installments') ?: 1;

        $totalFirst = $payment->getAdditionalInformation('cc_payment_first_amount');

        $amountFirst = $totalFirst;

        if ($installment > 1) {
            $storeId = $order->getStoreId();
            $interestFirst = $this->configCc->getInterestToAmount($installment, $totalFirst, $storeId);
            $amountFirst = $totalFirst + $interestFirst;
        }

        $totalSecondary = $grandTotal - $totalFirst;

        $installmentSecondary = $payment->getAdditionalInformation('cc_secondary_installments') ?: 1;

        $amountSecondary = $totalSecondary;

        if ($installmentSecondary > 1) {
            $storeId = $order->getStoreId();
            $interestSecondary = $this->configCc->getInterestToAmount($installmentSecondary, $totalSecondary, $storeId);
            $amountSecondary = $totalSecondary + $interestSecondary;
        }

        $result[self::AMOUNT] = $this->config->formatPrice(round($amountFirst + $amountSecondary, 2));

        $payment->setAdditionalInformation(
            'cc_payment_secondary_amount',
            round($amountSecondary, 2)
        );

        $payment->setAdditionalInformation(
            'cc_payment_first_amount',
            round($amountFirst, 2)
        );

        return $result;
    }
}
