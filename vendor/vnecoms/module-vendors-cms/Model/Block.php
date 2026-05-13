<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Vnecoms\VendorsCms\Api\Data\BlockInterface;
use Vnecoms\VendorsCms\Model\ResourceModel\Block as ResourceCmsBlock;
use Magento\Framework\Model\AbstractModel;

/**
 * CMS block model.
 *
 * @method ResourceCmsBlock _getResource()
 * @method ResourceCmsBlock getResource()
 */
class Block extends AbstractModel implements BlockInterface, IdentityInterface
{
    /*const BLOCK_ID      = 'block_id';
    const VENDOR_ID     = 'vendor_id';
    const IDENTIFIER    = 'identifier';
    const TITLE         = 'title';
    const CONTENT       = 'content';
    const CREATION_TIME = 'creation_time';
    const UPDATE_TIME   = 'update_time';
    const IS_ACTIVE     = 'is_active';*/
    /**
     * @var int
     */
    const STATUS_ENABLED = 1;

    /**
     * @var int
     */
    const STATUS_DISABLED = 0;

    /**
     * @var \Vnecoms\Vendors\Model\Vendor
     */
    protected $vendor;

    /**
     * CMS block cache tag.
     */
    const CACHE_TAG = 'vendorscms_block';

    /**
     * @var string
     */
    protected $_cacheTag = 'vendorscms_block';

    /**
     * Prefix of model events names.
     *
     * @var string
     */
    protected $_eventPrefix = 'vendorscms_block';

    /**
     */
    protected function _construct()
    {
        $this->_init('Vnecoms\VendorsCms\Model\ResourceModel\Block');
    }

    /**
     * Prevent blocks recursion.
     *
     * @return AbstractModel
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function beforeSave()
    {
        $needle = 'block_id="'.$this->getId().'"';
        if (false == strstr($this->getContent(), $needle)) {
            return parent::beforeSave();
        }
        throw new \Magento\Framework\Exception\LocalizedException(
            __('Make sure that static block content does not reference the block itself.')
        );
    }

    /**
     * Get identities.
     *
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG.'_'.$this->getId(), self::CACHE_TAG.'_'.$this->getIdentifier()];
    }

    /**
     * Prepare page's statuses.
     * Available event cms_page_get_available_statuses to customize statuses.
     *
     * @return array
     */
    public function getAvailableStatuses()
    {
        return [self::STATUS_ENABLED => __('Enabled'), self::STATUS_DISABLED => __('Disabled')];
    }

    /**
     * @return \Vnecoms\Vendors\Model\Vendor
     */
    public function getVendor()
    {
        if ($this->getRegisterVendor()) return $this->getRegisterVendor();
        if ($this->vendor) {
            return $this->vendor;
        } else {
            $this->vendor = \Magento\Framework\App\ObjectManager::getInstance()->create('Vnecoms\Vendors\Model\Vendor')
                ->load($this->getVendorId());

            return $this->vendor;
        }
    }

    /**
     * @return int
     */
    public function getVendorId()
    {
        return $this->getData(self::VENDOR_ID);
    }

    /**
     * @param int $vendorId
     *
     * @return $this
     */
    public function setVendorId($vendorId)
    {
        return $this->setData(self::VENDOR_ID, $vendorId);
    }

    /**
     * Retrieve block id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->getData(self::BLOCK_ID);
    }

    /**
     * Retrieve block identifier.
     *
     * @return string
     */
    public function getIdentifier()
    {
        return (string) $this->getData(self::IDENTIFIER);
    }

    /**
     * Retrieve block title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->getData(self::TITLE);
    }

    /**
     * Retrieve block content.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->getData(self::CONTENT);
    }

    /**
     * Retrieve block creation time.
     *
     * @return string
     */
    public function getCreationTime()
    {
        return $this->getData(self::CREATION_TIME);
    }

    /**
     * Retrieve block update time.
     *
     * @return string
     */
    public function getUpdateTime()
    {
        return $this->getData(self::UPDATE_TIME);
    }

    /**
     * Is active.
     *
     * @return bool
     */
    public function isActive()
    {
        return (bool) $this->getData(self::IS_ACTIVE);
    }

    /**
     * Set ID.
     *
     * @param int $id
     *
     * @return Block
     */
    public function setId($id)
    {
        return $this->setData(self::BLOCK_ID, $id);
    }

    /**
     * Set identifier.
     *
     * @param string $identifier
     *
     * @return Block
     */
    public function setIdentifier($identifier)
    {
        return $this->setData(self::IDENTIFIER, $identifier);
    }

    /**
     * Set title.
     *
     * @param string $title
     *
     * @return Block
     */
    public function setTitle($title)
    {
        return $this->setData(self::TITLE, $title);
    }

    /**
     * Set content.
     *
     * @param string $content
     *
     * @return Block
     */
    public function setContent($content)
    {
        return $this->setData(self::CONTENT, $content);
    }

    /**
     * Set creation time.
     *
     * @param string $creationTime
     *
     * @return Block
     */
    public function setCreationTime($creationTime)
    {
        return $this->setData(self::CREATION_TIME, $creationTime);
    }

    /**
     * Set update time.
     *
     * @param string $updateTime
     *
     * @return Block
     */
    public function setUpdateTime($updateTime)
    {
        return $this->setData(self::UPDATE_TIME, $updateTime);
    }

    /**
     * Set is active.
     *
     * @param bool|int $isActive
     *
     * @return Block
     */
    public function setIsActive($isActive)
    {
        return $this->setData(self::IS_ACTIVE, $isActive);
    }

    /**
     * Retrieve blocks option array.
     *
     * @todo: extract method as separate class
     *
     * @param bool $withGroup
     *
     * @return array
     */
    public function getBlocksOptionArray($withGroup = false)
    {
        /* @var $collection \Vnecoms\VendorsCms\Model\ResourceModel\Block\Collection */
        $collection = $this->getCollection();
        $blocks = [];
        foreach ($collection->toOptionArray() as $block) {
            //var_dump($block);die;
            $blocks[] = [
                'value' => '{{block block_id="'.$block['value'].'"}}',
                'label' => __('%1', $block['label']),
            ];
        }
        if ($withGroup && $blocks) {
            $blocks = ['label' => __('Select Blocks'), 'value' => $blocks];
        }

        return $blocks;
    }
}
