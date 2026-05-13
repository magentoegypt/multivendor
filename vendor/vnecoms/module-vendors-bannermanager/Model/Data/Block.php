<?php
/**
* Copyright 2017 Vnecoms. All rights reserved.
* See LICENSE.txt for license details.
*/

namespace Vnecoms\BannerManager\Model\Data;

use Vnecoms\BannerManager\Api\Data\BlockInterface;
use Magento\Framework\Api\AbstractExtensibleObject;
use Vnecoms\BannerManager\Api\Data\BlockExtensionInterface;

/**
 * Block data model
 * @codeCoverageIgnore
 */
class Block extends AbstractExtensibleObject implements BlockInterface
{
    /**
     * {@inheritdoc}
     */
    public function getBanner()
    {
        return $this->_get(self::BANNER);
    }

    /**
     * {@inheritdoc}
     */
    public function setBanner($banner)
    {
        return $this->setData(self::BANNER, $banner);
    }

    /**
     * {@inheritdoc}
     */
    public function getItems()
    {
        return $this->_get(self::ITEMS);
    }

    /**
     * {@inheritdoc}
     */
    public function setItems($items)
    {
        return $this->setData(self::ITEMS, $items);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * {@inheritdoc}
     */
    public function setExtensionAttributes(BlockExtensionInterface $extensionAttributes)
    {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
}

