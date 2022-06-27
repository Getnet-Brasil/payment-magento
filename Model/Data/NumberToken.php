<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Model\Data;

use Getnet\PaymentMagento\Api\Data\NumberTokenInterface;
use Magento\Framework\Api\AbstractSimpleObject;

/**
 * Class Number Token - Model data.
 */
class NumberToken extends AbstractSimpleObject implements NumberTokenInterface
{
    /**
     * @inheritdoc
     */
    public function getCardNumber()
    {
        return $this->_get(NumberTokenInterface::GETNET_CARD_NUMBER);
    }

    /**
     * @inheritdoc
     */
    public function setCardNumber($cardNumber)
    {
        return $this->setData(NumberTokenInterface::GETNET_CARD_NUMBER, $cardNumber);
    }
}
