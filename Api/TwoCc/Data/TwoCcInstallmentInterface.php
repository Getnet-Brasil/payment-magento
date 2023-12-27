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
 * Interface Two Cc Installment.
 *
 * @api
 *
 * @since 100.0.1
 */
interface TwoCcInstallmentInterface
{
    /**
     * @const string
     */
    public const GETNET_INSTALLMENT_SELECTED = 'installment_selected';

    /**
     * @const string
     */
    public const GETNET_INTEREST_AMOUNT = 'getnet_interest_amount';

    /**
     * @const string
     */
    public const BASE_GETNET_INTEREST_AMOUNT = 'base_getnet_interest_amount';

    /**
     * Return Getnet Two Installment Selected.
     *
     * @return int
     */
    public function getGetnetTwoCcInstallment();

    /**
     * Set the Getnet Two Cc Installment.
     *
     * @param int $twoCcInstallment
     *
     * @return $this
     */
    public function setGetnetTwoCcInstallment($twoCcInstallment);
}
