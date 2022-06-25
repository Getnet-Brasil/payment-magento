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
use Magento\Sales\Model\Order\Creditmemo;

/**
 * Class Two Cc Refund Handler - Defines the refund of an order.
 */
class TwoCcRefundHandler implements HandlerInterface
{
    /**
     * Result Code - Block name.
     */
    public const RESULT_CODE = 'RESULT_CODE';

    /**
     * Payments - Block name.
     */
    public const PAYMENTS = 'payments';

    /**
     * Response Pay Cancel Request Id - Block Name.
     */
    public const RESPONSE_COMBINED_ID = 'combined_id';

    /**
     * Response Pay Status - Block Name.
     */
    public const RESPONSE_STATUS = 'status';

    /**
     * Response Pay Status Approved - Value.
     */
    public const RESPONSE_STATUS_ACCEPTED = 'CANCELED';

    /**
     * Response Pay Status Denied - Value.
     */
    public const RESPONSE_STATUS_DENIED = 'ERROR';

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
        $isAccept = false;
        $isDenied = false;

        if (!isset($handlingSubject['payment'])
            || !$handlingSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new InvalidArgumentException('Payment data object should be provided');
        }

        $paymentDO = $handlingSubject['payment'];
        $payment = $paymentDO->getPayment();

        foreach ($response[self::PAYMENTS] as $paymentGetnet) {
            if ($paymentGetnet[self::RESPONSE_STATUS] === self::RESPONSE_STATUS_ACCEPTED) {
                $isAccept = true;
                $isDenied = false;
            }

            if ($paymentGetnet[self::RESPONSE_STATUS] === self::RESPONSE_STATUS_DENIED) {
                $isAccept = false;
                $isDenied = true;
            }
        }

        $payment->setTransactionId($response[self::RESPONSE_COMBINED_ID]);

        if ($isAccept) {
            $creditmemo = $payment->getCreditmemo();
            $creditmemo->setState(Creditmemo::STATE_REFUNDED);
        }

        if ($isDenied) {
            $creditmemo = $payment->getCreditmemo();
            $creditmemo->setState(Creditmemo::STATE_CANCELED);
        }

        if ($response[self::RESULT_CODE]) {
            $paymentDO->getPayment();
        }
    }
}
