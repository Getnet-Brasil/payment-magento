<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Api\TwoCc;

/**
 * Interface for obtaining interest on installments on Getnet payments in the order quote.
 *
 * @api
 */
interface GuestTwoCcInterestManagementInterface
{
    /**
     * Generate the list installments by credit card number.
     *
     * @param string                                                            $cartId
     * @param \Getnet\PaymentMagento\Api\TwoCc\Data\AmountForCalcInterface      $amountForCalc
     * @param \Getnet\PaymentMagento\Api\TwoCc\Data\IdxInTwoCcInterface         $idxInTwoCc
     * @param \Getnet\PaymentMagento\Api\TwoCc\Data\TwoCcInstallmentInterface   $twoCcInstallment
     *
     * @return \Magento\Quote\Api\Data\TotalsInterface Quote totals data.
     */
    public function generateGetnetTwoInterest(
        $cartId,
        \Getnet\PaymentMagento\Api\TwoCc\Data\AmountForCalcInterface $amountForCalc,
        \Getnet\PaymentMagento\Api\TwoCc\Data\IdxInTwoCcInterface $idxInTwoCc,
        \Getnet\PaymentMagento\Api\TwoCc\Data\TwoCcInstallmentInterface $twoCcInstallment,
    );
}
