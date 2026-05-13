<?php
/**
 * Catalog attributes configuration data container. Provides catalog attributes configuration data.
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Model\PageType\Config;

class Data extends \Magento\Framework\Config\Data
{
    /**
     * Cache ID for analysis configuration.
     *
     * @var string
     */
    const CACHE_ID = 'cms_page_types';

    /**
     * @param Reader                                   $reader
     * @param \Magento\Framework\Config\CacheInterface $cache
     */
    public function __construct(
        Reader $reader,
        \Magento\Framework\Config\CacheInterface $cache
    ) {
        parent::__construct($reader, $cache, self::CACHE_ID);
    }
}
