<?php
/**
* Copyright 2017 Vnecoms. All rights reserved.
* See LICENSE.txt for license details.
*/

namespace Vnecoms\BannerManager\Model\Data;

use Magento\Framework\Api\AbstractExtensibleObject;
use Vnecoms\BannerManager\Api\Data\BannerInterface;
use Vnecoms\BannerManager\Api\Data\BannerExtensionInterface;

/**
 * Banner data model
 * @codeCoverageIgnore
 */
class Banner extends AbstractExtensibleObject implements BannerInterface
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->_get(self::ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setId($id)
    {
        return $this->setData(self::ID, $id);
    }

    /**
     * {@inheritdoc}
     */
    public function getTitle()
    {
        return $this->_get(self::TITLE);
    }

    /**
     * {@inheritdoc}
     */
    public function setTitle($title)
    {
        return $this->setData(self::TITLE, $title);
    }

    /**
     * {@inheritdoc}
     */
    public function getStatus()
    {
        return $this->_get(self::STATUS);
    }

    /**
     * {@inheritdoc}
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * {@inheritdoc}
     */
    public function getItemIds()
    {

    }

    /**
     * {@inheritdoc}
     */
    public function setItemIds($itemIds)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function getFromDate()
    {
        return $this->_get(self::FROM_DATE);
    }

    /**
     * {@inheritdoc}
     */
    public function setFromDate($fromdate)
    {
        return $this->setData(self::FROM_DATE, $fromdate);
    }

    /**
     * {@inheritdoc}
     */
    public function getToDate()
    {
        return $this->_get(self::TO_DATE);
    }

    /**
     * {@inheritdoc}
     */
    public function setToDate($todate)
    {
        return $this->setData(self::TO_DATE, $todate);
    }

    /**
     * {@inheritdoc}
     */
    public function getDisplayArrows()
    {
        return $this->_get(self::DISPLAY_ARROWS);
    }

    /**
     * {@inheritdoc}
     */
    public function setDisplayArrows($displayArrows)
    {
        return $this->setData(self::DISPLAY_ARROWS, $displayArrows);
    }

    /**
     * {@inheritdoc}
     */
    public function getDisplayBullets()
    {
        return $this->_get(self::DISPLAY_BULLETS);
    }

    /**
     * {@inheritdoc}
     */
    public function setDisplayBullets($displayBullets)
    {
        return $this->setData(self::DISPLAY_BULLETS, $displayBullets);
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
    public function setExtensionAttributes(BannerExtensionInterface $extensionAttributes)
    {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
}
