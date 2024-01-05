<?php
/**
 * Getnet Payment Magento Module.
 *
 * Copyright © 2023 Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Model\Quote\Address\Total;

use Magento\Checkout\Model\Session;
use Magento\Framework\Phrase;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;
use Magento\Quote\Model\QuoteValidator;

/**
 * Class Getnet Interest - Model for implementing the Getnet Interest in Order totals.
 */
class GetnetInterest extends AbstractTotal
{
    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var QuoteValidator
     */
    protected $quoteValidator = null;

    /**
     * @var PaymentInterface
     */
    protected $payment;

    /**
     * Payment GetnetInterest constructor.
     *
     * @param QuoteValidator   $quoteValidator
     * @param Session          $checkoutSession
     * @param PaymentInterface $payment
     */
    public function __construct(
        QuoteValidator $quoteValidator,
        Session $checkoutSession,
        PaymentInterface $payment
    ) {
        $this->quoteValidator = $quoteValidator;
        $this->checkoutSession = $checkoutSession;
        $this->payment = $payment;
    }

    /**
     * Collect totals process.
     *
     * @param Quote                       $quote
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Total                       $total
     *
     * @return $this
     */
    public function collect(
        Quote $quote,
        ShippingAssignmentInterface $shippingAssignment,
        Total $total
    ) {
        parent::collect($quote, $shippingAssignment, $total);

        if (!count($shippingAssignment->getItems())) {
            return $this;
        }

        $psInterest = $quote->getGetnetInterestAmount();
        $basePsInterest = $quote->getBaseGetnetInterestAmount();

        $total->setGetnetInterestAmount($psInterest);
        $total->setBaseGetnetInterestAmount($basePsInterest);

        $total->setTotalAmount('getnet_interest_amount', $psInterest);
        $total->setBaseTotalAmount('getnet_interest_amount', $basePsInterest);

        $total->setGrandTotal((float) $total->getGrandTotal());
        $total->setBaseGrandTotal((float) $total->getBaseGrandTotal());

        return $this;
    }

    /**
     * Clear Values.
     *
     * @param Total $total
     */
    protected function clearValues(Total $total)
    {
        $total->setTotalAmount('subtotal', 0);
        $total->setBaseTotalAmount('subtotal', 0);
        $total->setTotalAmount('tax', 0);
        $total->setBaseTotalAmount('tax', 0);
        $total->setTotalAmount('discount_tax_compensation', 0);
        $total->setBaseTotalAmount('discount_tax_compensation', 0);
        $total->setTotalAmount('shipping_discount_tax_compensation', 0);
        $total->setBaseTotalAmount('shipping_discount_tax_compensation', 0);
        $total->setSubtotalInclTax(0);
        $total->setBaseSubtotalInclTax(0);
    }

    /**
     * Assign subtotal amount and label to address object.
     *
     * @param Quote $quote
     * @param Total $total
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function fetch(
        Quote $quote,
        Total $total
    ) {
        $result = null;
        $getnetInterest = $quote->getGetnetInterestAmount();
        $labelByPsInterest = $this->getLabelByGetnetInterest($getnetInterest);

        if ($getnetInterest) {
            $result = [
                'code'  => $this->getCode(),
                'title' => $labelByPsInterest,
                'value' => $getnetInterest,
            ];
        }

        return $result;
    }

    /**
     * Get Subtotal label.
     *
     * @return Phrase
     */
    public function getLabel()
    {
        return __('Installments Interest');
    }

    /**
     * Get Subtotal label by Getnet Interest.
     *
     * @param float $getnetInterest
     *
     * @return Phrase
     */
    public function getLabelByGetnetInterest($getnetInterest)
    {
        if ($getnetInterest >= 0) {
            return __('Installments Interest');
        }

        return __('Discount in cash');
    }
}
