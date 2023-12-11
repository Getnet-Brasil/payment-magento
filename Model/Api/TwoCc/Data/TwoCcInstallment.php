<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Model\Api\TwoCc\Data;

use Magento\Framework\Api\AbstractSimpleObject;
use Getnet\PaymentMagento\Api\TwoCc\Data\TwoCcInstallmentInterface;

/**
 * Class Two Cc Installment - Model data.
 */
class TwoCcInstallment extends AbstractSimpleObject implements TwoCcInstallmentInterface
{
    /**
     * @inheritdoc
     */
    public function getGetnetTwoCcInstallment()
    {
        return $this->_get(TwoCcInstallmentInterface::GETNET_INSTALLMENT_SELECTED);
    }

    /**
     * @inheritdoc
     */
    public function setGetnetTwoCcInstallment($twoCcInstallment)
    {
        return $this->setData(TwoCcInstallmentInterface::GETNET_INSTALLMENT_SELECTED, $twoCcInstallment);
    }
}
