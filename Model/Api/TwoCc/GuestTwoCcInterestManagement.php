<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Model\Api\TwoCc;

use Getnet\PaymentMagento\Api\TwoCc\Data\AmountForCalcInterface;
use Getnet\PaymentMagento\Api\TwoCc\Data\IdxInTwoCcInterface;
use Getnet\PaymentMagento\Api\TwoCc\Data\TwoCcInstallmentInterface;
use Getnet\PaymentMagento\Api\TwoCc\GuestTwoCcInterestManagementInterface;
use Getnet\PaymentMagento\Api\TwoCc\TwoCcInterestManagementInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;

/**
 * Class Two Cc Interest Management - Generate Interest in order.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GuestTwoCcInterestManagement implements GuestTwoCcInterestManagementInterface
{
    /**
     * @var QuoteIdMaskFactory
     */
    protected $quoteIdMaskFactory;

    /**
     * @var TwoCcInterestManagementInterface
     */
    protected $twoCcInterface;

    /**
     * @param \Magento\Quote\Model\QuoteIdMaskFactory                           $quoteIdMaskFactory
     * @param \Getnet\PaymentMagento\Api\TwoCc\TwoCcInterestManagementInterface $twoCcInterface
     */
    public function __construct(
        QuoteIdMaskFactory $quoteIdMaskFactory,
        TwoCcInterestManagementInterface $twoCcInterface
    ) {
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->twoCcInterface = $twoCcInterface;
    }

    /**
     * Generate List Installments.
     *
     * @param int                                                             $cartId
     * @param \Getnet\PaymentMagento\Api\TwoCc\Data\AmountForCalcInterface    $amountForCalc
     * @param \Getnet\PaymentMagento\Api\TwoCc\Data\IdxInTwoCcInterface       $idxInTwoCc
     * @param \Getnet\PaymentMagento\Api\TwoCc\Data\TwoCcInstallmentInterface $twoCcInstallment
     *
     * @throws CouldNotSaveException
     * @throws NoSuchEntityException
     *
     * @return array
     */
    public function generateGetnetTwoInterest(
        $cartId,
        AmountForCalcInterface $amountForCalc,
        IdxInTwoCcInterface $idxInTwoCc,
        TwoCcInstallmentInterface $twoCcInstallment
    ) {
        /** @var \Magento\Quote\Model\QuoteIdMask */
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');

        return $this->twoCcInterface->generateGetnetTwoInterest(
            $quoteIdMask->getQuoteId(),
            $amountForCalc,
            $idxInTwoCc,
            $twoCcInstallment
        );
    }
}
