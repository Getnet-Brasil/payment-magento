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
use Magento\Sales\Model\Order\Payment\Transaction;

/**
 * Deny Payment Handler - Set the flow when denying a payment.
 */
class DenyPaymentHandler implements HandlerInterface
{
    /**
     * Result Code - Block name.
     */
    public const RESULT_CODE = 'RESULT_CODE';

    /**
     * External Payment Id - Block Name.
     */
    public const GETNET_PAYMENT_ID = 'payment_id';

    /**
     * Response Pay Cancel Request Id - Block Name.
     */
    public const RESPONSE_CANCEL_REQUEST_ID = 'cancel_request_id';

    /**
     * Response Pay Status - Block Name.
     */
    public const RESPONSE_STATUS = 'status';

    /**
     * Response Pay Status Approved - Value.
     */
    public const RESPONSE_STATUS_ACCEPTED = 'ACCEPTED';

    /**
     * Response Pay Status Denied - Value.
     */
    public const RESPONSE_STATUS_DENIED = 'DENIED';

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

        if ($response[self::RESULT_CODE]) {
            $paymentDO = $handlingSubject['payment'];
            $payment = $paymentDO->getPayment();
            $paymentId = $response[self::GETNET_PAYMENT_ID];

            $payment->setTransactionId($paymentId.'-void');
            $payment->setParentTransactionId($paymentId);

            $order = $payment->getOrder();
            $amount = $order->getBaseGrandTotal();

            $payment->setPreparedMessage(__('Order Canceled.'));
            $payment->setIsTransactionPending(false);
            $payment->setIsTransactionDenied(true);
            $payment->setAmountCanceled($amount);
            $payment->setBaseAmountCanceled($amount);
            $payment->setShouldCloseParentTransaction(true);

            $payment->addTransaction(Transaction::TYPE_VOID);
        }
    }
}
