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
use Getnet\PaymentMagento\Api\TwoCc\Data\AmountForCalcInterface;

/**
 * Class Amount For Calc - Model data.
 */
class AmountForCalc extends AbstractSimpleObject implements AmountForCalcInterface
{
    /**
     * @inheritdoc
     */
    public function getGetnetAmountForCalc()
    {
        return $this->_get(AmountForCalcInterface::GETNET_AMOUNT_FOR_INSTALLMENT);
    }

    /**
     * @inheritdoc
     */
    public function setGetnetAmountForCalc($amount)
    {
        return $this->setData(AmountForCalcInterface::GETNET_AMOUNT_FOR_INSTALLMENT, $amount);
    }
}
