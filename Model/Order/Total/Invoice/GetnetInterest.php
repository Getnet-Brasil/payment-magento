<?php
/**
 * Getnet Payment Magento Module.
 *
 * Copyright © 2023 Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Model\Order\Total\Invoice;

use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Invoice\Total\AbstractTotal;

/**
 * Class Getnet Interest - Model for implementing the Getnet Interest in Invoice.
 */
class GetnetInterest extends AbstractTotal
{
    /**
     * Collect Getnet Interest.
     *
     * @param Invoice $invoice
     *
     * @return void
     */
    public function collect(Invoice $invoice)
    {
        $order = $invoice->getOrder();

        $psInterest = $order->getGetnetInterestAmount();
        $basePsInterest = $order->getBaseGetnetInterestAmount();

        $invoice->setGrandTotal($invoice->getGrandTotal() + $psInterest);
        $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $psInterest);
        $invoice->setGetnetInterestAmount($psInterest);
        $invoice->setBaseGetnetInterestAmount($basePsInterest);
        $invoice->setGetnetInterestAmountInvoiced($psInterest);
        $invoice->setBaseGetnetInterestAmountInvoiced($basePsInterest);

        $order->setGetnetInterestAmountInvoiced($psInterest);
        $order->setBaseGetnetInterestAmountInvoiced($basePsInterest);
    }
}
