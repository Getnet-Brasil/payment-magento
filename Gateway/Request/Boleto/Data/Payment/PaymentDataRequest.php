<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Gateway\Request\Boleto\Data\Payment;

use Getnet\PaymentMagento\Gateway\SubjectReader;
use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Getnet\PaymentMagento\Gateway\Request\Boleto\BoletoInitSchemaDataRequest;

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
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        SubjectReader $subjectReader
    ) {
        $this->subjectReader = $subjectReader;
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

        $result[BoletoInitSchemaDataRequest::DATA][self::PAYMENT] = [
            self::PAYMENT_ID        => $order->getOrderIncrementId(),
            self::PAYMENT_METHOD    => 'BOLETO'
        ];

        return $result;
    }
}
