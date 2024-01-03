<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Block\Sales\Info;

use Magento\Framework\Phrase;
use Magento\Payment\Block\ConfigurableInfo;

/**
 * Class Cc - Payment information.
 *
 * @SuppressWarnings(PHPMD)
 */
class Cc extends ConfigurableInfo
{
    /**
     * Returns label.
     *
     * @param string $field
     *
     * @return Phrase
     */
    protected function getLabel($field)
    {
        return __($field);
    }

    /**
     * Returns value view.
     *
     * @param string $field
     * @param string $value
     *
     * @return string | Phrase
     */
    protected function getValueView($field, $value)
    {
        if (is_array($value)) {
            return implode('; ', $value);
        }

        return parent::getValueView($field, $value);
    }
}
