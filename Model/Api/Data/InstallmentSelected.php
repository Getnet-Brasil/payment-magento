<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Model\Api\Data;

use Magento\Framework\Api\AbstractSimpleObject;
use Getnet\PaymentMagento\Api\Data\InstallmentSelectedInterface;

/**
 * Class Installment Selected - Model data.
 */
class InstallmentSelected extends AbstractSimpleObject implements InstallmentSelectedInterface
{
    /**
     * @inheritdoc
     */
    public function getInstallmentSelected()
    {
        return $this->_get(InstallmentSelectedInterface::GETNET_INSTALLMENT_SELECTED);
    }

    /**
     * @inheritdoc
     */
    public function setInstallmentSelected($installmentSelected)
    {
        return $this->setData(InstallmentSelectedInterface::GETNET_INSTALLMENT_SELECTED, $installmentSelected);
    }
}
