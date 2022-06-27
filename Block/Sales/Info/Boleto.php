<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Block\Sales\Info;

use Magento\Payment\Block\ConfigurableInfo;

/**
 * Class Boleto - Boleto payment information.
 */
class Boleto extends ConfigurableInfo
{
    /**
     * Pix Info template.
     *
     * @var string
     */
    protected $_template = 'Getnet_PaymentMagento::info/boleto/instructions.phtml';
}
