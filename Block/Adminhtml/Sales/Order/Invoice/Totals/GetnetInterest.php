<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Block\Adminhtml\Sales\Order\Invoice\Totals;

use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Template;

/**
 * Totals Getnet Interest Block - Method Invoice.
 */
class GetnetInterest extends Template
{
    /**
     * Get data (totals) source model.
     *
     * @return DataObject
     */
    public function getSource()
    {
        return $this->getParentBlock()->getSource();
    }

    /**
     * Get Invoice data.
     *
     * @return invoice
     */
    public function getInvoice()
    {
        return $this->getParentBlock()->getInvoice();
    }

    /**
     * Initialize payment Getnet Interest totals.
     *
     * @return $this
     */
    public function initTotals()
    {
        $this->getParentBlock();
        $this->getInvoice();
        $this->getSource();

        if (!$this->getSource()->getGetnetInterestAmount()
            || (int) $this->getSource()->getGetnetInterestAmount() === 0) {
            return $this;
        }

        $total = new DataObject(
            [
                'code'  => 'getnet_interest',
                'value' => $this->getSource()->getGetnetInterestAmount(),
                'label' => __('Installments Interest'),
            ]
        );

        $this->getParentBlock()->addTotalBefore($total, 'grand_total');

        return $this;
    }
}
