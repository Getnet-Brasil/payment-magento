<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Model\Cache\Type;

use Magento\Framework\App\Cache\Type\FrontendPool;
use Magento\Framework\Cache\Frontend\Decorator\TagScope;

/**
 * System / Cache Management / Cache type "Your Cache Type Label"
 */
class GetnetCache extends TagScope
{
    /**
     * Cache type code unique among all cache types
     */
    public const TYPE_IDENTIFIER = 'getnet';

    /**
     * The tag name that limits the cache cleaning scope within a particular tag
     */
    public const CACHE_TAG = 'OAUTH';

    /**
     * The lifetime from cache
     */
    public const CACHE_LIFETIME = 3000;

    /**
     * @param FrontendPool $cacheFrontendPool
     */
    public function __construct(FrontendPool $cacheFrontendPool)
    {
        parent::__construct(
            $cacheFrontendPool->get(self::TYPE_IDENTIFIER),
            self::CACHE_TAG
        );
    }
}
