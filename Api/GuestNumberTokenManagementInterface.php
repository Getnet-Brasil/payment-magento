<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Api;

/**
 *  Interface to get credit card number token in payments from Getnet in order quote.
 *
 * @api
 */
interface GuestNumberTokenManagementInterface
{
    /**
     * Generate the token number by credit card number.
     *
     * @param string                                               $cartId
     * @param \Getnet\PaymentMagento\Api\Data\NumberTokenInterface $cardNumber
     *
     * @return mixed
     */
    public function generateNumberToken(
        $cartId,
        \Getnet\PaymentMagento\Api\Data\NumberTokenInterface $cardNumber
    );
}
