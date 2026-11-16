<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Gateway\Response\GooglePay;

use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;

/**
 * Class DetailsHandler - Keeps the Google Pay payment details returned by the gateway (shown only in the admin).
 */
class DetailsHandler implements HandlerInterface
{
    /**
     * Response fields stored in the payment additional information (same key).
     * They must be listed in privateInfoKeys (etc/config.xml) so they are visible only in the admin.
     */
    private const FIELDS = [
        'payment_id',
        'acquirer',
        'issuer',
        'reason_code',
        'reason_message',
        'authorized_at',
        'transaction_link_identifier',
    ];

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

        $payment = $handlingSubject['payment']->getPayment();

        foreach (self::FIELDS as $field) {
            if (isset($response[$field]) && is_scalar($response[$field]) && $response[$field] !== '') {
                $payment->setAdditionalInformation($field, (string) $response[$field]);
            }
        }
    }
}
