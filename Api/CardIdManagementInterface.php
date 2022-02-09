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
 *  Interface to get card id data on Getnet payments in order quote.
 *
 * @api
 */
interface CardIdManagementInterface
{
    /**
     * Get Getnet card id details.
     *
     * @param int                                             $cartId
     * @param \Getnet\PaymentMagento\Api\Data\CardIdInterface $cardId
     *
     * @return string
     */
    public function getDetailsCardId(
        $cartId,
        \Getnet\PaymentMagento\Api\Data\CardIdInterface $cardId
    );
}
