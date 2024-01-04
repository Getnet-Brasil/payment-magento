<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Gateway\Response\Card\Operations;

use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order\Creditmemo;

/**
 * Class Refund Handler - Defines the refund of an order.
 */
class RefundHandler implements HandlerInterface
{
    /**
     * Result Code - Block name.
     */
    public const RESULT_CODE = 'RESULT_CODE';

    /**
     * Response Pay Cancel Request Id - Block Name.
     */
    public const RESPONSE_CANCEL_CUSTOM_KEY = 'cancel_custom_key';

    /**
     * Response Pay Status - Block Name.
     */
    public const RESPONSE_STATUS = 'status';

    /**
     * Response Pay Status Canceled - Value.
     */
    public const RESPONSE_STATUS_CANCELED = 'CANCELED';

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

        $paymentDO = $handlingSubject['payment'];
        $payment = $paymentDO->getPayment();

        $payment->setTransactionId($response[self::RESPONSE_CANCEL_CUSTOM_KEY]);

        if ($response[self::RESPONSE_STATUS] === self::RESPONSE_STATUS_CANCELED) {
            $creditmemo = $payment->getCreditmemo();
            $creditmemo->setState(Creditmemo::STATE_REFUNDED);
        }

        if ($response[self::RESPONSE_STATUS] === self::RESPONSE_STATUS_ACCEPTED) {
            $creditmemo = $payment->getCreditmemo();
            $creditmemo->setState(Creditmemo::STATE_OPEN);
        }

        if ($response[self::RESPONSE_STATUS] === self::RESPONSE_STATUS_DENIED) {
            $creditmemo = $payment->getCreditmemo();
            $creditmemo->setState(Creditmemo::STATE_CANCELED);
        }
    }
}
