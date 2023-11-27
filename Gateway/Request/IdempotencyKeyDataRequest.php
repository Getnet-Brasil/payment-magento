<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Gateway\Request;

use Getnet\PaymentMagento\Gateway\SubjectReader;
use Getnet\PaymentMagento\Gateway\Data\Order\OrderAdapterFactory;
use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;

/**
 * Class Idempotency Key Data Request - Payment amount structure.
 */
class IdempotencyKeyDataRequest implements BuilderInterface
{
    /**
     * Order Id Block Name.
     */
    public const IDEMPOTENCY_KEY = 'idempotency_key';
   
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

        $result[self::IDEMPOTENCY_KEY] = $orderAdapter->getOrderIncrementId();

        return $result;
    }
}
