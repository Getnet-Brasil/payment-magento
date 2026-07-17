<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace Getnet\PaymentMagento\Model\Adminhtml\Source;

use Getnet\PaymentMagento\Gateway\Config\Config;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class ApiType - Defines the Getnet API used for transactions.
 */
class ApiType implements OptionSourceInterface
{
    /**
     * Returns Options.
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => Config::API_TYPE_V2,
                'label' => __('API V2'),
            ],
            [
                'value' => Config::API_TYPE_GLOBAL,
                'label' => __('API Global'),
            ],
        ];
    }
}
