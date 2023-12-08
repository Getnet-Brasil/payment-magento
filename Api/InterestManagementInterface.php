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
 * Interface for obtaining interest on installments on Getnet payments in the order quote.
 *
 * @api
 */
interface InterestManagementInterface
{
    /**
     * Generate the list installments by credit card number.
     *
     * @param int                                                          $cartId
     * @param \Getnet\PaymentMagento\Api\Data\InstallmentSelectedInterface $installmentSelected
     *
     * @return \Magento\Quote\Api\Data\TotalsInterface Quote totals data.
     */
    public function generateGetnetInterest(
        $cartId,
        \Getnet\PaymentMagento\Api\Data\InstallmentSelectedInterface $installmentSelected
    );
}
