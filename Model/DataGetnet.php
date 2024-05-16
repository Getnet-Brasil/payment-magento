<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Getnet\PaymentMagento\Model\ResourceModel\DataGetnet as DataGetnetResourceModel;

class DataGetnet extends AbstractModel implements IdentityInterface
{
    /**
     * @var String
     */
    public const CACHE_TAG = 'getnet_transaction_log';

    /**
     * @var String
     */
    protected $_cacheTag = 'getnet_transaction_log';

    /**
     * @var String
     */
    protected $_eventPrefix = 'getnet_transaction_log';

    /**
     * Construct.
     */
    protected function _construct()
    {
        $this->_init(DataGetnetResourceModel::class);
    }

    /**
     * Get Indentities.
     *
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }
}