<?php

namespace Vnecoms\VendorsProductImportExport\Api\Data;

interface ImageInterface
{
    /**#@+
     * Constants defined for keys of the data array. Identical to the name of the getter in snake case
     */
    const FILE                = 'file';
    const SIZE              = 'size';
    const TYPE           = 'type';
    const URL           = 'url';
    const LAST_MOFIDY   = 'last_modify';

    /**
     * Get id
     *
     * @return string
     */
    public function getFile();

    /**
     * Set vendor id
     *
     * @param string $text
     * @return \Vnecoms\VendorsProductImportExport\Api\Data\ImageInterface
     */
    public function setFile($text);

    /**
     * Get id
     *
     * @return string
     */
    public function getSize();

    /**
     * Set vendor id
     *
     * @param string $text
     * @return \Vnecoms\VendorsProductImportExport\Api\Data\ImageInterface
     */
    public function setSize($text);

    /**
     * Get id
     *
     * @return string
     */
    public function getType();

    /**
     * Set vendor id
     *
     * @param string $text
     * @return \Vnecoms\VendorsProductImportExport\Api\Data\ImageInterface
     */
    public function setType($text);

    /**
     * Get id
     *
     * @return string
     */
    public function getUrl();

    /**
     * Set vendor id
     *
     * @param string $text
     * @return \Vnecoms\VendorsProductImportExport\Api\Data\ImageInterface
     */
    public function setUrl($text);

    /**
     * Get id
     *
     * @return string
     */
    public function getLastModify();

    /**
     * Set vendor id
     *
     * @param string $text
     * @return \Vnecoms\VendorsProductImportExport\Api\Data\ImageInterface
     */
    public function setLastModify($text);

}
