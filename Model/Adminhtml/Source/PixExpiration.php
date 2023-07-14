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
 * Class Pix Expiration - Defines time of expiration.
 */
class PixExpiration implements ArrayInterface
{
    /**
     * Returns Options.
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            '180'   => __('2:30 minutes'),
            '900'   => __('15 minutes'),
            '1800'  => __('30 minutes - recommended'),
        ];
    }
}
