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
interface CreateVaultManagementInterface
{
    /**
     * Create Vault.
     *
     * @param int $cartId
     * @param mixed $vaultData
     *
     * @return mixed
     */
    public function createVault(
        $cartId,
        $vaultData
    );
}
