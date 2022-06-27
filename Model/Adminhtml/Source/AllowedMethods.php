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
 * Class AllowedMethods - Defines product types.
 */
class AllowedMethods implements ArrayInterface
{
    /**
     * Returns Options.
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $options = [];
        $options[] = [
            'value' => 'credit',
            'label' => __('Credit'),
        ];
        $options[] = [
            'value' => 'debit',
            'label' => __('Debit'),
        ];
        $options[] = [
            'value' => 'pix',
            'label' => __('Pix'),
        ];
        $options[] = [
            'value' => 'qr_code',
            'label' => __('Qr Code'),
        ];

        return $options;
    }
}
