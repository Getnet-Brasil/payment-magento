<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Plugin;

use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;

/**
 * Class Payment Token Management Interface - Corrected duplicity.
 */
class PaymentToken
{
    /**
     * Around Save Token With Payment Link.
     *
     * @param PaymentTokenManagementInterface $payTokenManagement
     * @param callable                        $proceed
     * @param PaymentTokenInterface           $token
     * @param OrderPaymentInterface           $payment
     *
     * @return $proceed
     */
    public function aroundSaveTokenWithPaymentLink(
        PaymentTokenManagementInterface $payTokenManagement,
        callable $proceed,
        PaymentTokenInterface $token,
        OrderPaymentInterface $payment
    ): bool {
        $order = $payment->getOrder();

        if ($order->getCustomerIsGuest()) {
            return $proceed($token, $payment);
        }

        $existingToken = $payTokenManagement->getByGatewayToken(
            $token->getGatewayToken(),
            $payment->getMethodInstance()->getCode(),
            $order->getCustomerId()
        );

        if ($existingToken === null) {
            return $proceed($token, $payment);
        }

        $existingToken->addData($token->getData());

        return $proceed($existingToken, $payment);
    }
}
