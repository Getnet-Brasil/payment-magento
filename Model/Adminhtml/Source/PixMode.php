<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Model\Adminhtml\Source;

use Getnet\PaymentMagento\Gateway\Config\ConfigPix;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Pix flow preselected at checkout.
 */
class PixMode implements OptionSourceInterface
{
    /**
     * Options.
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => ConfigPix::MODE_BIOMETRIC, 'label' => __('Pix by Biometrics')],
            ['value' => ConfigPix::MODE_QRCODE, 'label' => __('Pix QR Code')],
        ];
    }
}
