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
 * Class ProductType - Defines product types.
 */
class ProductType implements ArrayInterface
{
    /**
     * Returns Options.
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            'cash_carry'        => __('Cash Carry'),
            'digital_content'   => __('Digital Content'),
            'digital_goods'     => __('Digital Goods'),
            'digital_physical'  => __('Digital Physical'),
            'gift_card'         => __('Gift Card'),
            'physical_goods'    => __('Physical Goods'),
            'renew_subs'        => __('Renew Subs'),
            'shareware'         => __('Shareware'),
            'service'           => __('Service'),
        ];
    }
}
