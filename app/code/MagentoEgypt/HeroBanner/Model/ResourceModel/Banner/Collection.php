<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HeroBanner\Model\ResourceModel\Banner;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use MagentoEgypt\HeroBanner\Model\Banner;
use MagentoEgypt\HeroBanner\Model\ResourceModel\Banner as BannerResource;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'banner_id';

    protected function _construct(): void
    {
        $this->_init(Banner::class, BannerResource::class);
    }
}
