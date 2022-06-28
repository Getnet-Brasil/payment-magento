<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Block\Customer;

use Getnet\PaymentMagento\Model\Ui\ConfigProviderBase;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Block\AbstractCardRenderer;

class CardRenderer extends AbstractCardRenderer
{
    /**
     * Can render specified token.
     *
     * @param PaymentTokenInterface $token
     *
     * @return bool
     */
    public function canRender(PaymentTokenInterface $token): bool
    {
        return $token->getPaymentMethodCode() === ConfigProviderBase::METHOD_CODE_CC;
    }

    /**
     * Get Last Numbers.
     *
     * @return string
     */
    public function getNumberLast4Digits(): string
    {
        return $this->getTokenDetails()['cc_last4'];
    }

    /**
     * Get Expiration Date.
     *
     * @return string
     */
    public function getExpDate(): string
    {
        return $this->getTokenDetails()['cc_exp_month'].'/'.$this->getTokenDetails()['cc_exp_year'];
    }

    /**
     * Get Icon Url.
     *
     * @return string
     */
    public function getIconUrl(): string
    {
        return $this->getIconForType($this->getTokenDetails()['cc_type'])['url'];
    }

    /**
     * Get Icon Height.
     *
     * @return int
     */
    public function getIconHeight(): int
    {
        return $this->getIconForType($this->getTokenDetails()['cc_type'])['height'];
    }

    /**
     *  Get Icon Width.
     *
     * @return int
     */
    public function getIconWidth(): int
    {
        return $this->getIconForType($this->getTokenDetails()['cc_type'])['width'];
    }
}
