<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Gateway\Request;

use Getnet\PaymentMagento\Gateway\Data\Order\OrderAdapterFactory;
use Getnet\PaymentMagento\Gateway\SubjectReader;
use InvalidArgumentException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;

/**
 * Class Type Deny Request - Refund data structure.
 */
class TypeDenyRequest implements BuilderInterface
{
    /**
     * Day Zero block name.
     */
    public const DAY_ZERO = 'day_zero';

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
     * @param SubjectReader         $subjectReader
     * @param OrderAdapterFactory   $orderAdapterFactory
     * @param DateTime              $date
     */
    public function __construct(
        SubjectReader $subjectReader,
        OrderAdapterFactory $orderAdapterFactory,
        DateTime $date
    ) {
        $this->subjectReader = $subjectReader;
        $this->orderAdapterFactory = $orderAdapterFactory;
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

        $result = [
            self::DAY_ZERO  => $dayZero,
        ];

        return $result;
    }
}
