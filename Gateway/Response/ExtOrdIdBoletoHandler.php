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
 * External Ordor Id Boleto Handler - Set the Getnet Order Id.
 */
class ExtOrdIdBoletoHandler implements HandlerInterface
{
    /**
     * Response Pay Boleto - Block name.
     */
    public const RESPONSE_BOLETO = 'boleto';

    /**
     * @const string
     */
    public const RESPONSE_BOLETO_ID = 'boleto_id';

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

        $order = $payment->getOrder();

        $payBoleto = $response[self::RESPONSE_BOLETO];

        $order->setExtOrderId($payBoleto[self::RESPONSE_BOLETO_ID]);
    }
}
