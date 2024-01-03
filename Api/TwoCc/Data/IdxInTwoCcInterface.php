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
 * Interface Idx in Two.
 *
 * @api
 *
 * @since 100.0.1
 */
interface IdxInTwoCcInterface
{
    /**
     * @const string
     */
    public const GETNET_IDX_TWO_CC = 'getnet_idx_two_cc';

    /**
     * Return Getnet Idx in Two Cc.
     *
     * @return int
     */
    public function getGetnetIdxInTwoCc();

    /**
     * Set the Getnet Idx in Two Cc.
     *
     * @param int $getnetIdxInTwoCc
     *
     * @return $this
     */
    public function setGetnetIdxInTwoCc($getnetIdxInTwoCc);
}
