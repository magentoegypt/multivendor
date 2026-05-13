<?php
/**
* Copyright 2017 Vnecoms. All rights reserved.
* See LICENSE.txt for license details.
*/

namespace Vnecoms\BannerManager\Api\Data;

use Vnecoms\BannerManager\Api\Data\BannerInterface;
use Vnecoms\BannerManager\Api\Data\ItemInterface;

/**
 * Block interface
 * @api
 */
interface BlockInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{
    /**#@+
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    const BANNER = 'banner';
    const ITEMS = 'items';
    /**#@-*/

    /**
     * Get banner
     *
     * @return BannerInterface|null
     */
    public function getBanner();

    /**
     * Set banner
     *
     * @param BannerInterface $banner
     * @return BlockInterface
     */
    public function setBanner($banner);

    /**
     * Get slides
     *
     * @return ItemInterface[]|null
     */
    public function getItems();

    /**
     * Set slides
     *
     * @param ItemInterface[] $slides
     * @return BlockInterface
     */
    public function setItems($slides);

    /**
     * Retrieve existing extension attributes object or create a new one
     *
     * @return \Vnecoms\BannerManager\Api\Data\BlockExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object
     *
     * @param \Vnecoms\BannerManager\Api\Data\BlockExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(\Vnecoms\BannerManager\Api\Data\BlockExtensionInterface $extensionAttributes);

}
