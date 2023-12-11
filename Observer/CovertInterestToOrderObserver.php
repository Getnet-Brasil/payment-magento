<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Getnet\PaymentMagento\Api\Data\InstallmentSelectedInterface;

/**
 * Class Covert Interest To Order Observer - Observer Class from Covert Interest To Order.
 */
class CovertInterestToOrderObserver implements ObserverInterface
{
    /**
     * Excecute convert interest.
     *
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        /* @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getData('order');
        /* @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getEvent()->getData('quote');

        $interest = $quote->getData(InstallmentSelectedInterface::GETNET_INTEREST_AMOUNT);
        $baseInterest = $quote->getData(InstallmentSelectedInterface::BASE_GETNET_INTEREST_AMOUNT);
        $order->setData(InstallmentSelectedInterface::GETNET_INTEREST_AMOUNT, $interest);
        $order->setData(InstallmentSelectedInterface::BASE_GETNET_INTEREST_AMOUNT, $baseInterest);
    }
}
