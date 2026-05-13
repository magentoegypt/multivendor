<?php

namespace Vnecoms\BannerManager\Model;

use Magento\Framework\Model\Context;
use Vnecoms\BannerManager\Api\Data\ItemInterface;

/**
 * Class Model Item
 * @category Vnecoms
 * @package  Vnecoms_BannerManager
 * @module   BannerManager
 * @author   Vnecoms Developer Team
 */
class Item extends \Magento\Framework\Model\AbstractModel
{
    /** banner item sort order random */
    const SORT_TYPE_RANDOM = 1;
    /** banner item sort order orderly */
    const SORT_TYPE_ORDERLY = 2;

    /**
     * Date conversion model.
     *
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_stdlibDateTime;

    /**
     * stdlib timezone.
     *
     * @var \Magento\Framework\Stdlib\DateTime\Timezone
     */
    protected $_stdTimezone;

    /**
     * Item constructor
     *
     * @param Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $stdlibDateTime
     * @param \Magento\Framework\Stdlib\DateTime\Timezone $stdTimezone
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Stdlib\DateTime\DateTime $stdlibDateTime,
        \Magento\Framework\Stdlib\DateTime\Timezone $stdTimezone,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_stdlibDateTime = $stdlibDateTime;
        $this->_stdTimezone = $stdTimezone;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }


    /**
     * Prepare parent constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Vnecoms\BannerManager\Model\ResourceModel\Item');
    }

    /**
     * Get item id
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->getData('item_id');
    }

    /**
     * Load Item Model
     *
     * @param int $id
     * @param null $field
     * @return $this
     */
    public function load($id, $field = null)
    {
        parent::load($id, $field);
        return $this;
    }

    /**
     * Get Item Title
     *
     * @return mixed
     */
    public function getTitle()
    {
        return $this->getData('title');
    }

    /**
     * Get Item Title
     *
     * @return mixed
     */
    public function setTitle($title)
    {
        return $this->setData('title', $title);
    }

    /**
     * Get Item Title
     *
     * @return mixed
     */
    public function getUrl()
    {
        return $this->getData('url');
    }

    /**
     * Get Item Title
     *
     * @return mixed
     */
    public function setUrl($url)
    {
        return $this->setData('url', $url);
    }

    /**
     * Get Item Title
     *
     * @return mixed
     */
    public function getItemShortDescription()
    {
        return $this->getData('short_description');
    }

    /**
     * Get Item Title
     *
     * @return mixed
     */
    public function setItemShortDescription($description)
    {
        return $this->setData('short_description', $description);
    }


    /**
     * Get item image alt
     *
     * @return mixed
     */
    public function getImageAlt()
    {
        return $this->getData('alt');
    }

    /**
     * Get item order position
     *
     * @return mixed
     */
    public function getItemPosition()
    {
        return $this->getData('sort_order');
    }


    /**
     * Set Item Position
     *
     * @param $position
     * @return Item
     */
    public function setItemPosition($position)
    {
        return $this->setData('sort_order', $position);
    }

    /**
     * Get item status
     *
     * @return mixed
     */
    public function getStatus()
    {
        return $this->getData('status');
    }


    /**
     * Set Item Status
     *
     * @param $status
     * @return Item
     */
    public function setStatus($status)
    {
        return $this->setData('status', $status);
    }

    /**
     *  Get item url
     *
     * @return mixed
     */
    public function getFileName()
    {
        return $this->getData('filename');
    }

    /**
     * Get banner id reference
     *
     * @return mixed
     */
    public function getBannerId()
    {
        return $this->getData('banner_id');
    }

    /**
     * Get Item by banner Id
     * @param $id
     * @return array
     */
    public function getItemByBannerId($id)
    {
        $item = $this->getResourceCollection()
            ->addFieldToFilter('banner_id', $id)
            ->addFieldToSelect('item_id')
            ->getData();

        return $item;
    }

    /**
     * Get array contain item collection of banner
     *
     * @param $bannerId
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getItemsFromBannerId($bannerId)
    {

        //$currentTime = $this->_stdTimezone->date()->format('Y-m-d H:i:s');
        //$now = (new \DateTime('now'));

        // Filter datetime
        $collections = $this->getResourceCollection()
            ->addFieldToFilter('banner_id', $bannerId)
            ->addFieldToFilter('status', 1)
            ->setOrder('sort_order', 'ASC')
            ->getData();

        return $collections;
    }

    /**
     * Set data before save
     *
     * @param array|string $key
     * @param null $value
     * @return $this
     */
    public function setData($key, $value = null)
    {
        return parent::setData($key, $value);
    }
}
