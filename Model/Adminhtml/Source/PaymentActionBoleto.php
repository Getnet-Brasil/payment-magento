<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Model\Adminhtml\Source;

use Magento\Payment\Model\Method\AbstractMethod;

/**
 * Class Payment Action Boleto - Adds payment action to boleto.
 */
class PaymentActionBoleto implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * To Options Array.
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => AbstractMethod::ACTION_ORDER,
                'label' => __('Order'),
            ],
        ];
    }
}
