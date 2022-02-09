<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Block\Sales\Form;

use Getnet\PaymentMagento\Gateway\Config\ConfigPix;
use Magento\Framework\View\Element\Template\Context;

/**
 * Class Pix - Form for payment by Pix.
 */
class Pix extends \Magento\Payment\Block\Form
{
    /**
     * Pix template.
     *
     * @var string
     */
    protected $_template = 'Getnet_PaymentMagento::form/pix.phtml';

    /**
     * @var ConfigPix
     */
    protected $configPix;

    /**
     * @param Context   $context
     * @param ConfigPix $configPix
     */
    public function __construct(
        Context $context,
        ConfigPix $configPix
    ) {
        parent::__construct($context);
        $this->configPix = $configPix;
    }

    /**
     * Title - Pix.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->configPix->getTitle();
    }

    /**
     * Instruction - Pix.
     *
     * @return string
     */
    public function getInstruction()
    {
        return $this->configPix->getInstructionCheckout();
    }

    /**
     * Expiration - Pix.
     *
     * @return string
     */
    public function getExpiration()
    {
        return $this->configPix->getExpirationFormat();
    }
}
