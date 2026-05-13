<?php
/**
 * Category data interface.
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCategory\Api\Data;

/**
 * @api
 */
interface CategoryInterface
{
    /**
     * @return int|null
     */
    public function getId();

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setId($id);

    /**
     * @return int
     */
    public function getRealId();

    /**
     * @param int $realId
     *
     * @return $this
     */
    public function setRealId($realId);

    /**
     * @return int
     */
    public function getVendorId();

    /**
     * @param int $vendorId
     *
     * @return $this
     */
    public function setVendorId($vendorId);

    /**
     * @return string
     */
    public function getDescription();

    /**
     * @param $description
     *
     * @return $this
     */
    public function setDescription($description);

    /**
     * Get parent category ID.
     *
     * @return int|null
     */
    public function getParentId();

    /**
     * Set parent category ID.
     *
     * @param int $parentId
     *
     * @return $this
     */
    public function setParentId($parentId);

    /**
     * Get category name.
     *
     * @return string
     */
    public function getName();

    /**
     * Set category name.
     *
     * @param string $name
     *
     * @return $this
     */
    public function setName($name);

    /**
     * Check whether category is active.
     *
     * @return bool|null
     */
    public function getIsActive();

    /**
     * Set whether category is active.
     *
     * @param bool $isActive
     *
     * @return $this
     */
    public function setIsActive($isActive);

    /**
     * Get category position.
     *
     * @return int|null
     */
    public function getPosition();

    /**
     * Set category position.
     *
     * @param int $position
     *
     * @return $this
     */
    public function setPosition($position);

    /**
     * Get category level.
     *
     * @return int|null
     */
    public function getLevel();

    /**
     * Set category level.
     *
     * @param int $level
     *
     * @return $this
     */
    public function setLevel($level);

    /**
     * @return string|null
     */
    public function getChildren();

    /**
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * @param string $createdAt
     *
     * @return $this
     */
    public function setCreatedAt($createdAt);

    /**
     * @return string|null
     */
    public function getUpdatedAt();

    /**
     * @param string $updatedAt
     *
     * @return $this
     */
    public function setUpdatedAt($updatedAt);

    /**
     * @return string|null
     */
    public function getPath();

    /**
     * @param string $path
     *
     * @return $this
     */
    public function setPath($path);

    /**
     * @return string|null
     */
    public function getIncludedInMenu();

    /**
     * @param string $config
     *
     * @return $this
     */
    public function setIncludedInMenu($config);
}
