<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace Getnet\PaymentMagento\Block\Sales\Info;

use Magento\Payment\Block\ConfigurableInfo;

/**
 * Class AbstractInfo - Locale-safe access to payment additional information.
 *
 * The keys of getSpecificInformation() are translated labels (__($field)),
 * so templates must not use them as lookup keys — on translated locales the
 * lookup silently fails. Templates read the raw additional information here.
 */
abstract class AbstractInfo extends ConfigurableInfo
{
    /**
     * Get Payment Additional Information value by key.
     *
     * @param string $key
     *
     * @return string|null
     */
    public function getPaymentAdditionalInfo(string $key): ?string
    {
        $value = $this->getInfo()->getAdditionalInformation($key);

        return $value === null ? null : (string) $value;
    }
}
