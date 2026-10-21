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
 * Class ApplePayButtonStyle - Defines the Apple Pay button styles.
 */
class ApplePayButtonStyle implements ArrayInterface
{
    /**
     * Returns Options.
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            'black' => __('Black'),
            'white' => __('White'),
            'white-outline' => __('White with outline'),
        ];
    }
}
