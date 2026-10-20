<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Gateway\Request\GooglePay\Operations;

use Getnet\PaymentMagento\Gateway\Request\Card\Operations\PaymentMethodDataRequest as CardPaymentMethodDataRequest;
use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;

/**
 * Class PaymentMethodDataRequest - Payment method of the cancel/refund of a Google Pay payment.
 *
 * Google Pay is always an immediate sale (CREDIT), there is no authorization to cancel.
 */
class PaymentMethodDataRequest extends CardPaymentMethodDataRequest
{
    /**
     * Build.
     *
     * @param array $buildSubject
     *
     * @return array
     */
    public function build(array $buildSubject)
    {
        if (!isset($buildSubject['payment'])
            || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new InvalidArgumentException('Payment data object should be provided');
        }

        return [
            self::PAYMENT_METHOD => 'CREDIT',
        ];
    }
}
