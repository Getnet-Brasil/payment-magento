<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Block\Sales\Form;

use Getnet\PaymentMagento\Gateway\Config\ConfigGetpay;
use Magento\Framework\View\Element\Template\Context;

/**
 * Class Getpay - Form for payment by boleto.
 *
 * @SuppressWarnings(PHPMD)
 */
class Getpay extends \Magento\Payment\Block\Form
{
    /**
     * Getpay template.
     *
     * @var string
     */
    protected $_template = 'Getnet_PaymentMagento::form/getpay.phtml';

    /**
     * @var ConfigGetpay
     */
    protected $configGetpay;

    /**
     * @param Context      $context
     * @param ConfigGetpay $configGetpay
     */
    public function __construct(
        Context $context,
        ConfigGetpay $configGetpay
    ) {
        parent::__construct($context);
        $this->configGetpay = $configGetpay;
    }

    /**
     * Title - Getpay.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->configGetpay->getTitle();
    }

    /**
     * Instruction - Getpay.
     *
     * @return string
     */
    public function getInstruction()
    {
        return $this->configGetpay->getInstructionCheckout();
    }

    /**
     * Expiration - Getpay.
     *
     * @return string
     */
    public function getExpiration()
    {
        return $this->configGetpay->getExpirationFormat();
    }
}
