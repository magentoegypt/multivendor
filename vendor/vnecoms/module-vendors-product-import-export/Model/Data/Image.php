<?php

namespace Vnecoms\VendorsProductImportExport\Model\Data;

use Magento\Framework\Api\AbstractSimpleObject;
use Vnecoms\VendorsProductImportExport\Api\Data\ImageInterface;

/**
 * Class DocumentContent
 *
 * @package Ecommage\CustomerAvatar\Model\Data
 */
class Image extends AbstractSimpleObject implements ImageInterface
{
    /**
     * Get id
     *
     * @return string
     */
    public function getFile() {
        return $this->_get(self::FILE);
    }

    /**
     * Set vendor id
     *
     * @param string $text
     * @return \Vnecoms\VendorsProductImportExport\Api\Data\ImageInterface
     */
    public function setFile($text) {
        return $this->setData(self::FILE, $text);
    }

    /**
     * Get id
     *
     * @return string
     */
    public function getSize() {
        return $this->_get(self::SIZE);
    }

    /**
     * Set vendor id
     *
     * @param string $text
     * @return \Vnecoms\VendorsProductImportExport\Api\Data\ImageInterface
     */
    public function setSize($text) {
        return $this->setData(self::SIZE, $text);
    }

    /**
     * Get id
     *
     * @return string
     */
    public function getType() {
        return $this->_get(self::TYPE);
    }

    /**
     * Set vendor id
     *
     * @param string $text
     * @return \Vnecoms\VendorsProductImportExport\Api\Data\ImageInterface
     */
    public function setType($text) {
        return $this->setData(self::TYPE, $text);
    }

    /**
     * Get id
     *
     * @return string
     */
    public function getUrl() {
        return $this->_get(self::URL);
    }

    /**
     * Set vendor id
     *
     * @param string $text
     * @return \Vnecoms\VendorsProductImportExport\Api\Data\ImageInterface
     */
    public function setUrl($text) {
        return $this->setData(self::URL, $text);
    }

    /**
     * Get id
     *
     * @return string
     */
    public function getLastModify() {
        return $this->_get(self::LAST_MOFIDY);
    }

    /**
     * Set vendor id
     *
     * @param string $text
     * @return \Vnecoms\VendorsProductImportExport\Api\Data\ImageInterface
     */
    public function setLastModify($text) {
        return $this->setData(self::LAST_MOFIDY, $text);
    }
}
