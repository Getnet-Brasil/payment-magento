<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Model\Order\Total\Creditmemo;

use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Creditmemo\Total\AbstractTotal;

/**
 * Class Getnet Interest - Model for implementing the Getnet Interest in Creditmemo.
 */
class GetnetInterest extends AbstractTotal
{
    /**
     * Collect Getnet Interest.
     *
     * @param Creditmemo $creditmemo
     *
     * @return void
     */
    public function collect(Creditmemo $creditmemo)
    {
        $order = $creditmemo->getOrder();

        $psInterest = $order->getGetnetInterestAmount();
        $basePsInterest = $order->getBaseGetnetInterestAmount();

        $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $psInterest);
        $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $psInterest);
        $creditmemo->setGetnetInterestAmount($psInterest);
        $creditmemo->setBaseGetnetInterestAmount($basePsInterest);
        $order->setGetnetInterestAmountRefunded($psInterest);
        $order->setBaseGetnetInterestAmountRefunded($basePsInterest);
    }
}
