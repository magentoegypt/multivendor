<?php
/**
* Copyright 2017 Vnecoms. All rights reserved.
* See LICENSE.txt for license details.
*/

namespace Vnecoms\BannerManager\Api\Data;

/**
 * Banner interface
 */
interface BannerInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{
    /**#@+
     * Constants defined for keys of the data array.
     * Identical to the name of the getter in snake case
     */
    const ID = 'banner_id';
    const TITLE = 'title';
    const STATUS = 'status';
    const IDENTIFIER = 'identifier';
    const EFFECT = 'effect';
    const SPEED = 'speed';
    const DELAY = 'delay';
    const FROM_DATE = 'from_date';
    const TO_DATE = 'to_date';
    const DISPLAY_ARROWS = 'display_arrows';
    const DISPLAY_BULLETS = 'display_bullets';
    /**#@-*/

    /**
     * Get ID
     *
     * @return int|null
     */
    public function getId();

    /**
     * Set ID
     *
     * @param int $id
     * @return $this
     */
    public function setId($id);

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle();

    /**
     * Set title
     *
     * @param string $title
     * @return $this
     */
    public function setTitle($title);

    /**
     * Get status
     *
     * @return int
     */
    public function getStatus();

    /**
     * Set status
     *
     * @param int $status
     * @return $this
     */
    public function setStatus($status);

    /**
     * Get item ids
     *
     * @return int[]
     */
    public function getItemIds();

    /**
     * Set slide ids
     *
     * @param int[] $itemIds
     * @return $this
     */
    public function setItemIds($itemIds);

    /**
     * Get date display from
     * @return string
     */
    public function getFromDate();

    /**
     * @param string $fromdate
     * @return $this
     */
    public function setFromDate($fromdate);

    /**
     * Get date display to
     * @return string
     */
    public function getToDate();

    /**
     * @param string $todate
     * @return $this
     */
    public function setToDate($todate);

    /**
     * Retrieve existing extension attributes object or create a new one.
     *
     * @return \Vnecoms\BannerManager\Api\Data\BannerExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     *
     * @param \Vnecoms\BannerManager\Api\Data\BannerExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(\Vnecoms\BannerManager\Api\Data\BannerExtensionInterface $extensionAttributes);
}
