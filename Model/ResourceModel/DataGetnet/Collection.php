<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Model\ResourceModel\DataGetnet;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Getnet\PaymentMagento\Model\DataGetnet;
use Getnet\PaymentMagento\Model\ResourceModel\DataGetnet as DataGetnetResourceModel;

class Collection extends AbstractCollection
{
    /**
     * @var String
     */
    protected $_idFieldName = 'entity_id';

    /**
     * @var String
     */
    protected $_eventPrefix = 'getnet_transaction_log_collection';

    /**
     * @var String
     */
    protected $_eventObject = 'getnet_log_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(DataGetnet::class, DataGetnetResourceModel::class);
    }
}
