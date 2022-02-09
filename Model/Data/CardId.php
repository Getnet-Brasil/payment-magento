<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Model\Data;

use Getnet\PaymentMagento\Api\Data\CardIdInterface;
use Magento\Framework\Api\AbstractSimpleObject;

/**
 * Class Card Id - Model data.
 */
class CardId extends AbstractSimpleObject implements CardIdInterface
{
    /**
     * @inheritdoc
     */
    public function getCardId()
    {
        return $this->_get(CardIdInterface::GETNET_CARD_ID);
    }

    /**
     * @inheritdoc
     */
    public function setCardId($cardId)
    {
        return $this->setData(CardIdInterface::GETNET_CARD_ID, $cardId);
    }
}
