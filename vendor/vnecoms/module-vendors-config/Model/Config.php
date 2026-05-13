<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsConfig\Model;

use Magento\Framework\Exception\LocalizedException;
use Vnecoms\VendorsConfig\Api\Data\ConfigInterface;

/**
 * @method int getCustomerId();
 * @method int getCredit();
 */
class Config extends \Magento\Framework\Model\AbstractModel implements \Vnecoms\VendorsConfig\Api\Data\ConfigInterface
{
    /**
     * Prefix of model events names
     * @var string
     */
    protected $_eventPrefix = 'vendor_config';

    /**
     * @var \Vnecoms\VendorsConfig\Helper\Data
     */
    protected $_configHelper;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Serialize
     */
    protected $serialize;

    /**
     * Config constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param ResourceModel\Config|null $resource
     * @param ResourceModel\Config\Collection|null $resourceCollection
     * @param \Vnecoms\VendorsConfig\Helper\Data $configHelper
     * @param \Magento\Framework\Serialize\Serializer\Serialize $serialize
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Vnecoms\VendorsConfig\Model\ResourceModel\Config $resource = null,
        \Vnecoms\VendorsConfig\Model\ResourceModel\Config\Collection $resourceCollection = null,
        \Vnecoms\VendorsConfig\Helper\Data $configHelper,
        \Magento\Framework\Serialize\Serializer\Serialize $serialize,
        array $data = []
    ) {
        $this->_configHelper = $configHelper;
        $this->serialize = $serialize;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Vnecoms\VendorsConfig\Model\ResourceModel\Config');
    }

    /**
     * Get id
     *
     * @return int|null
     */
    public function getConfigId() {
        return $this->getData(self::ID);
    }

    /**
     * Set vendor id
     *
     * @param int $id
     * @return \Vnecoms\VendorsConfig\Api\Data\ConfigInterface
     */
    public function setConfigId($id) {
        return $this->setData(self::ID, $id);
    }

    /**
     * Get message
     *
     * @return string
     */
    public function getVendorId() {
        return $this->getData(self::VENDOR_ID);
    }

    /**
     * Set transaction amount
     *
     * @param string $vendorId
     * @return \Vnecoms\VendorsConfig\Api\Data\ConfigInterface
     */
    public function setVendorId($vendorId) {
        return $this->setData(self::VENDOR_ID, $vendorId);
    }

    /**
     * Get additional info
     *
     * @return string
     */
    public function getPath() {
        return $this->getData(self::PATH);
    }

    /**
     * Set additional info
     *
     * @param string $path
     * @return \Vnecoms\VendorsConfig\Api\Data\ConfigInterface
     */
    public function setPath($path) {
        return $this->setData(self::PATH, $path);
    }

    /**
     * Get is read
     *
     * @return string
     */
    public function getValue() {
        return $this->getData(self::VALUE);
    }

    /**
     * Set is read
     *
     * @param string $value
     * @return \Vnecoms\VendorsConfig\Api\Data\ConfigInterface
     */
    public function setValue($value) {
        return $this->setData(self::VALUE, $value);
    }

    /**
     * Get is reached
     *
     * @return string
     */
    public function getStoreId() {
        return $this->getData(self::STORE_ID);
    }

    /**
     * Set store Id
     *
     * @param string $storeId
     * @return \Vnecoms\VendorsConfig\Api\Data\ConfigInterface
     */
    public function setStoreId($storeId) {
        return $this->setData(self::STORE_ID, $storeId);
    }
}
