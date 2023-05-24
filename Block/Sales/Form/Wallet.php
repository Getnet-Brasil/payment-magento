<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Block\Sales\Form;

use Getnet\PaymentMagento\Gateway\Config\ConfigCc;
use Getnet\PaymentMagento\Gateway\Config\ConfigWallet;
use Magento\Backend\Model\Session\Quote;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Framework\View\Element\Template\Context;

/**
 * Class Wallet - Form for payment by Wallet.
 *
 * @SuppressWarnings(PHPMD)
 */
class Wallet extends \Magento\Payment\Block\Form
{
    /**
     * Wallet template.
     *
     * @var string
     */
    protected $_template = 'Getnet_PaymentMagento::form/wallet.phtml';

    /**
     * @var Quote
     */
    protected $session;

    /**
     * @var ConfigCc
     */
    protected $configCc;

    /**
     * @var ConfigWallet
     */
    protected $configWallet;

    /**
     * @var PriceHelper
     */
    private $priceHelper;

    /**
     * @param Context      $context
     * @param Quote        $session
     * @param ConfigCc     $configCc
     * @param ConfigWallet $configWallet
     * @param PriceHelper  $priceHelper
     */
    public function __construct(
        Context $context,
        Quote $session,
        ConfigCc $configCc,
        ConfigWallet $configWallet,
        PriceHelper $priceHelper
    ) {
        parent::__construct($context);
        $this->session = $session;
        $this->configCc = $configCc;
        $this->configWallet = $configWallet;
        $this->priceHelper = $priceHelper;
    }

    /**
     * Title - Wallet.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->configWallet->getTitle();
    }

    /**
     * Instruction - Wallet.
     *
     * @return string
     */
    public function getInstruction()
    {
        return $this->configWallet->getInstructionCheckout();
    }

    /**
     * Select Installment - Cc.
     *
     * @return array
     */
    public function getSelectInstallments(): array
    {
        $total = $this->session->getQuote()->getGrandTotal();
        $installments = $this->getInstallments($total);

        return $installments;
    }

    /**
     * Installments - Cc.
     *
     * @param float $amount
     *
     * @return array
     */
    public function getInstallments($amount): array
    {
        $interestByInst = $this->configCc->getInfoInterest();
        $plotlist = [];
        foreach ($interestByInst as $key => $interest) {
            if ($key > 0) {
                $plotValue = $this->getInterestSimple($amount, $interest, $key);
                $plotValue = number_format((float) $plotValue, 2, '.', '');
                $installmentPrice = $this->priceHelper->currency($plotValue, true, false);
                $plotlist[$key] = $key.__('x of ').$installmentPrice;
            }
        }

        return $plotlist;
    }

    /**
     * Interest Simple - Cc.
     *
     * @param float $total
     * @param float $interest
     * @param int   $portion
     *
     * @return float
     */
    public function getInterestSimple($total, $interest, $portion): float
    {
        if ($interest) {
            $taxa = $interest / 100;
            $valinterest = $total * $taxa;

            return ($total + $valinterest) / $portion;
        }

        return $total / $portion;
    }
}
