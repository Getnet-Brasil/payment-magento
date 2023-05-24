<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Block\Sales\Form;

use Getnet\PaymentMagento\Gateway\Config\ConfigBoleto;
use Magento\Framework\View\Element\Template\Context;

/**
 * Class Boleto - Form for payment by boleto.
 *
 * @SuppressWarnings(PHPMD)
 */
class Boleto extends \Magento\Payment\Block\Form
{
    /**
     * Boleto template.
     *
     * @var string
     */
    protected $_template = 'Getnet_PaymentMagento::form/boleto.phtml';

    /**
     * @var ConfigBoleto
     */
    protected $configBoleto;

    /**
     * @param Context      $context
     * @param ConfigBoleto $configBoleto
     */
    public function __construct(
        Context $context,
        ConfigBoleto $configBoleto
    ) {
        parent::__construct($context);
        $this->configBoleto = $configBoleto;
    }

    /**
     * Title - Boleto.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->configBoleto->getTitle();
    }

    /**
     * Instruction - Boleto.
     *
     * @return string
     */
    public function getInstruction()
    {
        return $this->configBoleto->getInstructionCheckout();
    }

    /**
     * Expiration - Boleto.
     *
     * @return string
     */
    public function getExpiration()
    {
        return $this->configBoleto->getExpirationFormat();
    }
}
