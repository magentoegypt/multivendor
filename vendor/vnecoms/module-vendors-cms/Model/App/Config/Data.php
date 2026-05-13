<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Model\App\Config;

use Magento\Framework\Config\CacheInterface;

class Data extends \Magento\Framework\Config\Data
{
    /**
     * Cache ID for analysis configuration.
     *
     * @var string
     */
    const CACHE_ID = 'cms_app';

    /**
     * Constructor.
     *
     * @param Reader         $reader  Config file reader.
     * @param CacheInterface $cache   Cache instance.
     * @param string         $cacheId Default config cache id.
     */
    public function __construct(Reader $reader, CacheInterface $cache, $cacheId = self::CACHE_ID)
    {
        parent::__construct($reader, $cache, $cacheId);
    }
}
