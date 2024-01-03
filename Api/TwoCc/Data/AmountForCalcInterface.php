<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace Getnet\PaymentMagento\Api\TwoCc\Data;

/**
 * Interface Amount For Calc.
 *
 * @api
 *
 * @since 100.0.1
 */
interface AmountForCalcInterface
{
    /**
     * @const string
     */
    public const GETNET_AMOUNT_FOR_INSTALLMENT = 'amount';

    /**
     * Return Getnet Amount For Calc.
     *
     * @return float
     */
    public function getGetnetAmountForCalc();

    /**
     * Set the Getnet Amount For Calc.
     *
     * @param float $amount
     *
     * @return $this
     */
    public function setGetnetAmountForCalc($amount);
}
