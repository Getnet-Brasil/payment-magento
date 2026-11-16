<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Observer;

use Getnet\PaymentMagento\Model\Interest\InstallmentGuard;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Blocks the order before it reaches Getnet when installments differ from what the interest was priced for.
 */
class ValidateInstallmentInterestObserver implements ObserverInterface
{
    /**
     * @var InstallmentGuard
     */
    private $installmentGuard;

    /**
     * @param InstallmentGuard $installmentGuard
     */
    public function __construct(InstallmentGuard $installmentGuard)
    {
        $this->installmentGuard = $installmentGuard;
    }

    /**
     * Execute.
     *
     * @param Observer $observer
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Quote\Model\Quote|null $quote */
        $quote = $observer->getEvent()->getData('quote');

        if ($quote) {
            $this->installmentGuard->validate($quote);
        }
    }
}
