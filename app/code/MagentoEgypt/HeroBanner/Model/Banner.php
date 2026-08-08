<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HeroBanner\Model;

use Magento\Framework\Model\AbstractModel;
use MagentoEgypt\HeroBanner\Model\ResourceModel\Banner as BannerResource;

/**
 * One row of the homepage hero band — either a carousel slide or a side tile.
 */
class Banner extends AbstractModel
{
    public const SLOT_HERO = 'hero';
    public const SLOT_TILE = 'tile';

    protected $_eventPrefix = 'magentoegypt_hero_banner';

    protected function _construct(): void
    {
        $this->_init(BannerResource::class);
    }
}
