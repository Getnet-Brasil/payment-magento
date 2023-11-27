<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Gateway\Request\Card\Operations;

use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;

/**
 * Class External Payment Id - Payment Id structure.
 */
class PaymentMethodDataRequest implements BuilderInterface
{
    /**
     * @var string
     */
    public const PAYMENT_METHOD = 'payment_method';

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
        $order = $payment->getOrder();

        return [
            self::PAYMENT_METHOD => 'CREDIT_AUTHORIZATION',
        ];
    }
}
