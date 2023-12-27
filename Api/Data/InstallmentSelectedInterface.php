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
 * Interface Installment Selected - Interest.
 *
 * @api
 *
 * @since 100.0.1
 */
interface InstallmentSelectedInterface
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
     * Return Installment Selected.
     *
     * @return int
     */
    public function getInstallmentSelected();

    /**
     * Set the Installment Selected.
     *
     * @param int $installmentSelected
     *
     * @return $this
     */
    public function setInstallmentSelected($installmentSelected);
}
