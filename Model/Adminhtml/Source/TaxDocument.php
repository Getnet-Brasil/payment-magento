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
 * Class TaxDocument - Defines tax document.
 */
class TaxDocument implements ArrayInterface
{
    /**
     * Returns Options.
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            null       => __('Please select'),
            'customer' => __('by customer form (taxvat - customer account)'),
            'address'  => __('by address form (vat_id - checkout)'),
        ];
    }
}
