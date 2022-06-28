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
use InvalidArgumentException;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;

/**
 * Class Refund Request - Refund data structure.
 */
class RefundRequest implements BuilderInterface
{
    /**
     * External Payment Id - Block Name.
     */
    public const GETNET_PAYMENT_ID = 'payment_id';

    /**
     * Amount block name.
     */
    public const CANCEL_AMOUNT = 'cancel_amount';

    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @var Config
     */
    protected $configPayment;

    /**
     * @var ConfigCc
     */
    protected $configCc;

    /**
     * @param ConfigInterface $config
     * @param Config          $configPayment
     * @param ConfigCc        $configCc
     */
    public function __construct(
        ConfigInterface $config,
        Config $configPayment,
        ConfigCc $configCc
    ) {
        $this->config = $config;
        $this->configPayment = $configPayment;
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

        $creditmemo = $payment->getCreditMemo();

        $totalCreditmemo = $creditmemo->getGrandTotal();

        $installment = $payment->getAdditionalInformation('cc_installments') ?: 1;

        $result = [
            self::GETNET_PAYMENT_ID => str_replace('-capture', '', $payment->getLastTransId()),
            self::CANCEL_AMOUNT     => $this->configPayment->formatPrice($totalCreditmemo),
        ];

        if ($installment > 1) {
            $order = $paymentDO->getOrder();
            $storeId = $order->getStoreId();
            $amountInterest = $this->configCc->getInterestToAmount($installment, $totalCreditmemo, $storeId);
            $total = $totalCreditmemo + $amountInterest;
            $result = [
                self::GETNET_PAYMENT_ID => str_replace('-capture', '', $payment->getLastTransId()),
                self::CANCEL_AMOUNT     => $this->configPayment->formatPrice($total),
            ];
        }

        return $result;
    }
}
