<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db;

class DataGetnet extends Db\AbstractDb
{
    /**
     * Construct
     */
    protected function _construct()
    {
        $this->_init('getnet_transaction_log', 'entity_id');
    }
}
