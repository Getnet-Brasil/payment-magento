<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Api\Data;

/**
 * Interface Number Token - Data Number Card.
 */
interface NumberTokenInterface
{
    /**
     * @const string
     */
    public const GETNET_CARD_NUMBER = 'getnet_card_number';

    /**
     * Get Card Number for tokenize.
     *
     * @return string
     */
    public function getCardNumber();

    /**
     * Set Card Number for tokenize.
     *
     * @param string $cardNumber
     *
     * @return void
     */
    public function setCardNumber($cardNumber);
}
