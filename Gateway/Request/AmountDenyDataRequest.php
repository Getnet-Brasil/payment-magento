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

/**
 * Class Amount Deny Data Request - Payment amount structure.
 */
class AmountDenyDataRequest implements BuilderInterface
{
    /**
     * Amount block name.
     */
    public const CANCEL_AMOUNT = 'cancel_amount';

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

        $paymentDO = $this->subjectReader->readPayment($buildSubject);

        $result = [];

        $order = $paymentDO->getOrder();

        $grandTotal = $order->getGrandTotalAmount();

        $payment = $paymentDO->getPayment();

        $installment = $payment->getAdditionalInformation('cc_installments') ?: 1;

        $result[self::CANCEL_AMOUNT] = ceil($this->config->formatPrice($grandTotal));

        if ($installment > 1) {
            $order = $paymentDO->getOrder();
            $storeId = $order->getStoreId();
            $amountInterest = $this->configCc->getInterestToAmount($installment, $grandTotal, $storeId);
            $total = $grandTotal + $amountInterest;
            $result = [
                self::CANCEL_AMOUNT     => $this->config->formatPrice($total),
            ];
        }

        return $result;
    }
}
