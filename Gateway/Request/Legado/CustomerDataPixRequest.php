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
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;

/**
 * Class Customer Data Pix Request - Customer structure.
 */
class CustomerDataPixRequest implements BuilderInterface
{
    /**
     * Unique user id.
     * Required.
     */
    public const CUSTOMER_ID = 'customer_id';

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
     * @param OrderAdapterFactory $orderAdapterFactory
     */
    public function __construct(
        SubjectReader $subjectReader,
        OrderAdapterFactory $orderAdapterFactory
    ) {
        $this->subjectReader = $subjectReader;
        $this->orderAdapterFactory = $orderAdapterFactory;
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

        /** @var OrderAdapterFactory $orderAdapter * */
        $orderAdapter = $this->orderAdapterFactory->create(
            ['order' => $payment->getOrder()]
        );

        $billingAddress = $orderAdapter->getBillingAddress();

        $result[self::CUSTOMER_ID] = $billingAddress->getEmail();

        return $result;
    }
}
