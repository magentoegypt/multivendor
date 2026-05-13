<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Api\Data;

/**
 * RMA Type interface.
 * @api
 */
interface StatusInterface
{
    /**#@+
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    const STATUS_ID      = 'status_id';
    const TITLE         = 'title';
    const CODE         = 'code';
    const IS_MAIN       = 'is_main';
    const STATUS = 'status';

    /**#@-*/

    /**
     * Get ID
     *
     * @return int|null
     */
    public function getId();


    /**
     * Get title
     *
     * @return string|null
     */
    public function getTitle();
    /**
     * Get code
     *
     * @return string|null
     */
    public function getCode();

    /**
     * Get content template
     *
     * @return string|null
     */
    public function getIsMain();

    /**
     * Get status
     *
     * @return string|null
     */
    public function getStatus();

    /**
     * Set ID
     *
     * @param int $id
     * @return \Vnecoms\RMA\Api\Data\StatusInterface
     */
    public function setId($id);


    /**
     * Set title
     *
     * @param string $title
     * @return \Vnecoms\RMA\Api\Data\StatusInterface
     */
    public function setTitle($title);
    
    /**
     * Set Code
     *
     * @param string $code
     * @return \Vnecoms\RMA\Api\Data\StatusInterface
     */
    public function setCode($code);

    /**
     * Set Is Main
     *
     * @param string $isMain
     * @return \Vnecoms\RMA\Api\Data\StatusInterface
     */
    public function setIsMain($isMain);

    /**
     * Set status
     *
     * @param string $status
     * @return  \Vnecoms\RMA\Api\Data\StatusInterface
     */
    public function setStatus($status);

    /**
     * Get display label
     *
     * @return \Vnecoms\RMA\Api\Data\StatusInterface[]|null
     */
    public function getStoreLabels();

    /**
     * Set display label
     *
     * @param \\Vnecoms\RMA\Api\Data\StatusInterface[]|null $storeLabels
     * @return $this
     */
    public function setStoreLabels(array $storeLabels = null);
}
