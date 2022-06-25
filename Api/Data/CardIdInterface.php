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
 * Interface Card Id - Data Card.
 */
interface CardIdInterface
{
    /**
     * @const string
     */
    public const GETNET_CARD_ID = 'getnet_card_id';

    /**
     * Get Card Id.
     *
     * @return string
     */
    public function getCardId();

    /**
     * Set Get Card Id.
     *
     * @param string $cardId
     *
     * @return void
     */
    public function setCardId($cardId);
}
