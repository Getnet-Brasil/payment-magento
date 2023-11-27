<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Gateway\Request\Card\Operations;

use Getnet\PaymentMagento\Gateway\Config\Config;
use Getnet\PaymentMagento\Gateway\Config\ConfigCc;
use Getnet\PaymentMagento\Gateway\SubjectReader;
use InvalidArgumentException;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;

/**
 * Class Refund Request - Refund data structure.
 */
class AmountRefundDataRequest implements BuilderInterface
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

        $dayZero = false;

        $result = [];

        $paymentDO = $this->subjectReader->readPayment($buildSubject);

        $payment = $paymentDO->getPayment();

        $order = $payment->getOrder();

        $creditmemo = $payment->getCreditMemo();

        $totalCreditmemo = $creditmemo->getGrandTotal();

        $installment = $payment->getAdditionalInformation('cc_installments') ?: 1;

        $result = [
            self::AMOUNT => $this->config->formatPrice($totalCreditmemo),
        ];

        if ($installment > 1) {
            $storeId = $order->getStoreId();
            $amountInterest = $this->configCc->getInterestToAmount($installment, $totalCreditmemo, $storeId);
            $total = $totalCreditmemo + $amountInterest;
            $result = [
                self::AMOUNT => $this->config->formatPrice($total),
            ];
        }

        return $result;
    }
}
