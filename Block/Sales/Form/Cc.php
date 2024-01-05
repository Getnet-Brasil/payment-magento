<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Block\Sales\Form;

use Getnet\PaymentMagento\Gateway\Config\Config as ConfigBase;
use Getnet\PaymentMagento\Gateway\Config\ConfigCc;
use Getnet\PaymentMagento\Gateway\Config\ConfigCcVault;
use Magento\Backend\Model\Session\Quote;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Framework\Url;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Block\Form\Cc as NativeCc;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\Config;
use Magento\Vault\Model\VaultPaymentInterface;

/**
 * Class Cc - Form for payment by cc.
 *
 * @SuppressWarnings(PHPMD)
 */
class Cc extends NativeCc
{
    /**
     * Cc template.
     *
     * @var string
     */
    protected $_template = 'Getnet_PaymentMagento::form/cc.phtml';

    /**
     * @var Quote
     */
    protected $session;

    /**
     * @var ConfigCc
     */
    protected $configCc;

    /**
     * @var Data
     */
    private $paymentDataHelper;

    /**
     * @var ConfigBase
     */
    private $configBase;

    /**
     * @var PriceHelper
     */
    private $priceHelper;

    /**
     * @param Context     $context
     * @param Config      $paymentConfig
     * @param Quote       $session
     * @param ConfigCc    $configCc
     * @param configBase  $configBase
     * @param Data        $paymentDataHelper
     * @param PriceHelper $priceHelper
     * @param array       $data
     */
    public function __construct(
        Context $context,
        Config $paymentConfig,
        Quote $session,
        ConfigCc $configCc,
        ConfigBase $configBase,
        Data $paymentDataHelper,
        PriceHelper $priceHelper,
        array $data = []
    ) {
        parent::__construct($context, $paymentConfig, $data);
        $this->session = $session;
        $this->configBase = $configBase;
        $this->configCc = $configCc;
        $this->priceHelper = $priceHelper;
        $this->paymentDataHelper = $paymentDataHelper;
    }

    /**
     * Title - Cc.
     *
     * @return string
     */
    public function getTitle(): ?string
    {
        return $this->configCc->getTitle();
    }

    /**
     * Url For Tokenize.
     *
     * @var string
     */
    public function getUrlForTokenize()
    {
        return $this->getUrl('payment/order/NumberToken');
    }

    /**
     * Use tax document capture - Cc.
     *
     * @var bool
     */
    public function getTaxDocumentCapture()
    {
        return $this->configCc->hasUseTaxDocumentCapture();
    }

    /**
     * Use phone capture - Cc.
     *
     * @var bool
     */
    public function getPhoneCapture()
    {
        return $this->configCc->hasUsePhoneCapture();
    }

    /**
     * Get configured vault payment.
     *
     * @return VaultPaymentInterface
     */
    private function getVaultPayment()
    {
        return $this->paymentDataHelper->getMethodInstance(ConfigCcVault::METHOD);
    }

    /**
     * Check if vault enabled.
     *
     * @return bool
     */
    public function isVaultEnabled()
    {
        $vaultPayment = $this->getVaultPayment();

        return $vaultPayment->isActive();
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
