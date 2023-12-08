<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Model\Api;

use Magento\Quote\Model\QuoteIdMaskFactory;
use Getnet\PaymentMagento\Api\Data\InstallmentSelectedInterface;
use Getnet\PaymentMagento\Api\GuestInterestManagementInterface;
use Getnet\PaymentMagento\Api\InterestManagementInterface;

/**
 * Class Guest Interest Management - Generate interest.
 */
class GuestInterestManagement implements GuestInterestManagementInterface
{
    /**
     * @var QuoteIdMaskFactory
     */
    protected $quoteIdMaskFactory;

    /**
     * @var InterestManagementInterface
     */
    protected $cardNumberInterface;

    /**
     * @param \Magento\Quote\Model\QuoteIdMaskFactory                $quoteIdMaskFactory
     * @param \Getnet\PaymentMagento\Api\InterestManagementInterface $cardNumberInterface
     */
    public function __construct(
        QuoteIdMaskFactory $quoteIdMaskFactory,
        InterestManagementInterface $cardNumberInterface
    ) {
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->cardNumberInterface = $cardNumberInterface;
    }

    /**
     * Generate List Installments.
     *
     * @param string                                                       $cartId
     * @param \Getnet\PaymentMagento\Api\Data\InstallmentSelectedInterface $installmentSelected
     *
     * @return array
     */
    public function generateGetnetInterest(
        $cartId,
        InstallmentSelectedInterface $installmentSelected
    ) {
        /** @var \Magento\Quote\Model\QuoteIdMask */
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');

        return $this->cardNumberInterface->generateGetnetInterest(
            $quoteIdMask->getQuoteId(),
            $installmentSelected
        );
    }
}
