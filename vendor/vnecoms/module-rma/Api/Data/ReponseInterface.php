<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Api\Data;

/**
 * RMA Reponse interface.
 * @api
 */
interface ReponseInterface
{
    /**#@+
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    const TYPE_ID      = 'reponse_id';
    const TITLE         = 'title';
    const CONTENT       = 'content';
    const STATUS = 'status';

    /**#@-*/

    /**
     * Get ID
     *
     * @return int|null
     */
    public function getId();


    /**
     * Get reponse
     *
     * @return string|null
     */
    public function getTitle();

    /**
     * Get content template
     *
     * @return string|null
     */
    public function getContent();

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
     * @return \Vnecoms\RMA\Api\Data\ReponseInterface
     */
    public function setId($id);


    /**
     * Set reponse
     *
     * @param string $reponse
     * @return \Vnecoms\RMA\Api\Data\ReponseInterface
     */
    public function setTitle($reponse);

    /**
     * Set Content
     *
     * @param string $content
     * @return \Vnecoms\RMA\Api\Data\ReponseInterface
     */
    public function setContent($content);

    /**
     * Set status
     *
     * @param string $status
     * @return  \Vnecoms\RMA\Api\Data\ReponseInterface
     */
    public function setStatus($status);
}
