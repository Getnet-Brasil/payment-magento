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
 * Interface for obtaining a list of installments in Getnet payments in the order quote.
 *
 * @api
 */
interface GuestInterestManagementInterface
{
    /**
     * Generate the list installments by credit card.
     *
     * @param string                                                       $cartId
     * @param \Getnet\PaymentMagento\Api\Data\InstallmentSelectedInterface $installmentSelected
     *
     * @return \Magento\Quote\Api\Data\TotalsInterface Quote totals data.
     */
    public function generateGetnetInterest(
        $cartId,
        \Getnet\PaymentMagento\Api\Data\InstallmentSelectedInterface $installmentSelected
    );
}
