<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Model;

use Getnet\PaymentMagento\Api\Data\NumberTokenInterface;
use Getnet\PaymentMagento\Api\GuestNumberTokenManagementInterface;
use Getnet\PaymentMagento\Api\NumberTokenManagementInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;

/**
 * Class Number Token Management - Generate Number Token by Card Number.
 */
class GuestNumberTokenManagement implements GuestNumberTokenManagementInterface
{
    /**
     * @var QuoteIdMaskFactory
     */
    protected $quoteIdMaskFactory;

    /**
     * @var NumberTokenManagementInterface
     */
    protected $cardNumberInterface;

    /**
     * @param \Magento\Quote\Model\QuoteIdMaskFactory                   $quoteIdMaskFactory
     * @param \Getnet\PaymentMagento\Api\NumberTokenManagementInterface $cardNumberInterface
     */
    public function __construct(
        QuoteIdMaskFactory $quoteIdMaskFactory,
        NumberTokenManagementInterface $cardNumberInterface
    ) {
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->cardNumberInterface = $cardNumberInterface;
    }

    /**
     * Save Getnet Interest.
     *
     * @param string               $cartId
     * @param NumberTokenInterface $cardNumber
     *
     * @return array
     */
    public function generateNumberToken(
        $cartId,
        NumberTokenInterface $cardNumber
    ) {
        /** @var \Magento\Quote\Model\QuoteIdMask */
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');

        return $this->cardNumberInterface->generateNumberToken(
            $quoteIdMask->getQuoteId(),
            $cardNumber
        );
    }
}
