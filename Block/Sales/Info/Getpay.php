<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Block\Sales\Info;

/**
 * Class Getpay - Getpay payment information.
 *
 * @SuppressWarnings(PHPMD)
 */
class Getpay extends AbstractInfo
{
    /**
     * Getpay Info template.
     *
     * @var string
     */
    protected $_template = 'Getnet_PaymentMagento::info/getpay/instructions.phtml';
}
