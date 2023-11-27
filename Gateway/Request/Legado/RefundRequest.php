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
use Getnet\PaymentMagento\Gateway\Data\Order\OrderAdapterFactory;
use Getnet\PaymentMagento\Gateway\SubjectReader;
use InvalidArgumentException;
use Magento\Framework\Stdlib\DateTime\DateTime;
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
     * Day Zero block name.
     */
    public const DAY_ZERO = 'day_zero';

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
     * @var DateTime
     */
    protected $date;

    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * @var OrderAdapterFactory
     */
    protected $orderAdapterFactory;

    /**
     * @param SubjectReader       $subjectReader
     * @param ConfigInterface     $config
     * @param Config              $configPayment
     * @param OrderAdapterFactory $orderAdapterFactory
     * @param ConfigCc            $configCc
     * @param DateTime            $date
     */
    public function __construct(
        SubjectReader $subjectReader,
        ConfigInterface $config,
        Config $configPayment,
        OrderAdapterFactory $orderAdapterFactory,
        ConfigCc $configCc,
        DateTime $date
    ) {
        $this->subjectReader = $subjectReader;
        $this->config = $config;
        $this->configPayment = $configPayment;
        $this->orderAdapterFactory = $orderAdapterFactory;
        $this->configCc = $configCc;
        $this->date = $date;
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

        /** @var OrderAdapterFactory $orderAdapter */
        $orderAdapter = $this->orderAdapterFactory->create(
            ['order' => $order]
        );

        $orderDateCreated = $orderAdapter->getCreatedAt();
        $dateCreated = $this->date->gmtDate('Y-m-d', $orderDateCreated);
        $dateCompare = $this->date->gmtDate('Y-m-d');

        if ($dateCompare === $dateCreated) {
            $dayZero = true;
        }

        $creditmemo = $payment->getCreditMemo();

        $totalCreditmemo = $creditmemo->getGrandTotal();

        $installment = $payment->getAdditionalInformation('cc_installments') ?: 1;

        $result = [
            self::GETNET_PAYMENT_ID => str_replace('-capture', '', $payment->getLastTransId()),
            self::CANCEL_AMOUNT     => $this->configPayment->formatPrice($totalCreditmemo),
            self::DAY_ZERO          => $dayZero,
        ];

        if ($installment > 1) {
            $storeId = $order->getStoreId();
            $amountInterest = $this->configCc->getInterestToAmount($installment, $totalCreditmemo, $storeId);
            $total = $totalCreditmemo + $amountInterest;
            $result = [
                self::GETNET_PAYMENT_ID => str_replace('-capture', '', $payment->getLastTransId()),
                self::CANCEL_AMOUNT     => $this->configPayment->formatPrice($total),
                self::DAY_ZERO          => $dayZero,
            ];
        }

        return $result;
    }
}
