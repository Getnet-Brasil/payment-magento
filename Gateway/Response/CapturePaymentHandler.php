<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Gateway\Response;

use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;

/**
 * Class Capture Payment Handler - Define flow when capturing a payment.
 *
 * @SuppressWarnings(PHPCPD)
 */
class CapturePaymentHandler implements HandlerInterface
{
    /**
     * @const TXN ID
     */
    public const TXN_ID = 'TXN_ID';

    /**
     * Handles.
     *
     * @param array $handlingSubject
     * @param array $response
     *
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        if (!isset($handlingSubject['payment'])
            || !$handlingSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new InvalidArgumentException('Payment data object should be provided');
        }

        if ($response['RESULT_CODE']) {
            $paymentDO = $handlingSubject['payment'];

            $payment = $paymentDO->getPayment();
            $order = $payment->getOrder();
            $amount = $order->getGrandTotal();

            $payment->registerCaptureNotification($amount);
        }
    }
}
