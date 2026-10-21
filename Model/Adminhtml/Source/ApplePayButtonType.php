<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class ApplePayButtonType - Defines the Apple Pay button types.
 */
class ApplePayButtonType implements ArrayInterface
{
    /**
     * Returns Options.
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            'pay'       => __('Pay'),
            'buy'       => __('Buy'),
            'check-out' => __('Checkout'),
            'order'     => __('Order'),
            'plain'     => __('Plain'),
        ];
    }
}
