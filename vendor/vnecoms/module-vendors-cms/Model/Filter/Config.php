<?php

namespace Vnecoms\VendorsCms\Model\Filter;

use Vnecoms\VendorsCms\Model\Filter\Config\Reader;
use Magento\Framework\Config\CacheInterface;

/**
 * Analysis configuration.
 */
class Config extends \Magento\Framework\Config\Data
{
    /**
     * Cache ID for analysis configuration.
     *
     * @var string
     */
    const CACHE_ID = 'cms_filter_config';

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

    /**
     * Get filters list.
     *
     * @return array[]
     */
    public function getFilters()
    {
        return $this->get();
    }

    /**
     * Get filter by ID.
     *
     * @param string $filterId
     *
     * @return array
     */
    public function getFilter($filterId)
    {
        return $this->get($filterId);
    }
}
