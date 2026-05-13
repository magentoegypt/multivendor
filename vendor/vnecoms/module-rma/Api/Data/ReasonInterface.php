<?php
/**
 * Created by PhpStorm.
 * User: nvhai
 * Date: 12/23/2016
 * Time: 10:38 AM
 */
namespace Vnecoms\RMA\Api\Data;

/**
 * RMA Reason interface.
 * @api
 */
interface ReasonInterface
{
    /**#@+
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    const REASON_ID      = 'reason_id';
    const TITLE         = 'title';
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
     * @return \Vnecoms\RMA\Api\Data\ReasonInterface
     */
    public function setId($id);


    /**
     * Set title
     *
     * @param string $title
     * @return \Vnecoms\RMA\Api\Data\ReasonInterface
     */
    public function setTitle($title);

    /**
     * Set Is Main
     *
     * @param string $isMain
     * @return \Vnecoms\RMA\Api\Data\ReasonInterface
     */
    public function setIsMain($isMain);

    /**
     * Set status
     *
     * @param string $status
     * @return  \Vnecoms\RMA\Api\Data\ReasonInterface
     */
    public function setStatus($status);
    /**
     * Get display label
     *
     * @return \Vnecoms\RMA\Api\Data\ReasonInterface[]|null
     */
    public function getStoreLabels();

    /**
     * Set display label
     *
     * @param \\Vnecoms\RMA\Api\Data\ReasonInterface[]|null $storeLabels
     * @return $this
     */
    public function setStoreLabels(array $storeLabels = null);
}
