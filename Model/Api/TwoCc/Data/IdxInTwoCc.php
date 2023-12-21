<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Model\Api\TwoCc\Data;

use Getnet\PaymentMagento\Api\TwoCc\Data\IdxInTwoCcInterface;
use Magento\Framework\Api\AbstractSimpleObject;

/**
 * Class Idx In Two Cc - Model data.
 */
class IdxInTwoCc extends AbstractSimpleObject implements IdxInTwoCcInterface
{
    /**
     * @inheritdoc
     */
    public function getGetnetIdxInTwoCc()
    {
        return $this->_get(IdxInTwoCcInterface::GETNET_IDX_TWO_CC);
    }

    /**
     * @inheritdoc
     */
    public function setGetnetIdxInTwoCc($getnetIdxInTwoCc)
    {
        return $this->setData(IdxInTwoCcInterface::GETNET_IDX_TWO_CC, $getnetIdxInTwoCc);
    }
}
