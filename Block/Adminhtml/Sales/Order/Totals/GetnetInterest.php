<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Block\Adminhtml\Sales\Order\Totals;

use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Model\Order;

/**
 * Totals Getnet Interest Block - Method Order.
 */
class GetnetInterest extends Template
{
    /**
     * @var Order
     */
    protected $order;

    /**
     * @var DataObject
     */
    protected $source;

    /**
     * Type display in Full Sumary.
     *
     * @return bool
     */
    public function displayFullSummary()
    {
        return true;
    }

    /**
     * Get data (totals) source model.
     *
     * @return DataObject
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Get Store.
     *
     * @return string
     */
    public function getStore()
    {
        return $this->order->getStore();
    }

    /**
     * Get Order.
     *
     * @return order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Initialize payment Getnet Interest totals.
     *
     * @return $this
     */
    public function initTotals()
    {
        $parent = $this->getParentBlock();
        $this->order = $parent->getOrder();
        $this->source = $parent->getSource();

        if (!$this->source->getGetnetInterestAmount()
            || (int) $this->source->getGetnetInterestAmount() === 0) {
            return $this;
        }

        $getnetInterest = $this->source->getGetnetInterestAmount();
        if ($getnetInterest) {
            $label = $this->getLabel($getnetInterest);
            $psInterestAmount = new DataObject(
                [
                    'code'   => 'getnet_interest',
                    'strong' => false,
                    'value'  => $getnetInterest,
                    'label'  => $label,
                ]
            );

            if ((int) $getnetInterest !== 0.0000) {
                $parent->addTotal($psInterestAmount, 'getnet_interest');
            }
        }

        return $this;
    }

    /**
     * Get Subtotal label.
     *
     * @param string|null $getnetInterest
     *
     * @return Phrase
     */
    public function getLabel($getnetInterest)
    {
        if ($getnetInterest >= 0) {
            return __('Installments Interest');
        }

        return __('Discount in cash');
    }
}
