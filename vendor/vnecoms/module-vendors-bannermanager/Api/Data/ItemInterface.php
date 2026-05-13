<?php
/**
* Copyright 2017 Vnecoms. All rights reserved.
* See LICENSE.txt for license details.
*/

namespace Vnecoms\BannerManager\Api\Data;

/**
 * Item interface
 * @api
 */
interface ItemInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{
    /**#@+
     * Constants defined for keys of the data array.
     * Identical to the name of the getter in snake case
     */
    const ID = 'item_id';
    const TITLE = 'title';
    const STATUS = 'status';
    const ALT = 'alt';
    const URL = 'url';
    const SORT_ORDER = 'sort_order';
    const SHORT_DESCRIPTION = 'short_description';
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
     * Get url
     *
     * @return string
     */
    public function getUrl();

    /**
     * Set url
     *
     * @param string $url
     * @return $this
     */
    public function setUrl($url);

    /**
     * Retrieve existing extension attributes object or create a new one.
     *
     * @return \Vnecoms\BannerManager\Api\Data\ItemExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     *
     * @param \Vnecoms\BannerManager\Api\Data\ItemExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(\Vnecoms\BannerManager\Api\Data\ItemExtensionInterface $extensionAttributes);
}
