<?php

namespace Vnecoms\BannerManager\Model;

use Vnecoms\BannerManager\Api\Data\BannerInterface;
use Vnecoms\BannerManager\Api\Data\BannerExtensionInterface;

/**
 * Class Model Banner
 * @category Vnecoms
 * @package  Vnecoms_BannerManager
 * @module   BannerManager
 * @author   Vnecoms Developer Team
 */
class Banner extends \Magento\Framework\Model\AbstractModel implements BannerInterface
{
    /** banner enable/disable */
    const XML_CONFIG_BANNERMANAGER = 'bannermanager/general/is_enable';
    /** status banner enable */
    const STATUS_ENABLE = 1;
    /** status banner disable */
    const STATUS_DISABLE = 0;

    /**
     * CMS block cache tag.
     */
    const CACHE_TAG = 'bannermanager_banner';

    /** @var string  */
    protected $_formFieldHtmlIdPrefix = 'banner_';

    /** @var  \Magento\Store\Model\StoreManagerInterface */
    protected $_storeManager;

    /** @var int  */
    protected $_storeViewId;

    /** @var \Magento\Framework\Logger\Monolog  */
    protected $_monolog;

    /** @var \Vnecoms\BannerManager\Model\BannerFactory  */
    protected $_bannerFactory;

    /**
     * @var \Vnecoms\Vendors\Model\Vendor
     */
    protected $vendor;

    protected $_storeId;

    /**
     * Banner constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Backend\App\Action\Context $actionContext
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Logger\Monolog $monolog
     * @param \Vnecoms\BannerManager\Model\BannerFactory $bannerFactory
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Backend\App\Action\Context $actionContext,
        \Magento\Framework\Registry $registry,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Logger\Monolog $monolog,
        \Vnecoms\BannerManager\Model\BannerFactory $bannerFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {

        $this->_storeManager = $storeManager;
        $this->_monolog      = $monolog;
        $this->_bannerFactory = $bannerFactory;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Init resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Vnecoms\BannerManager\Model\ResourceModel\Banner');
    }

    /**
     * Get Form Field Prefix
     *
     * @return string
     */
    public function getFormFieldHtmlIdPrefix()
    {
        return $this->_formFieldHtmlIdPrefix;
    }

    /**
     * @return \Vnecoms\Vendors\Model\Vendor
     */
    public function getVendor()
    {
        if ($this->vendor) {
            return $this->vendor;
        } else {
            $this->vendor = \Magento\Framework\App\ObjectManager::getInstance()->create('Vnecoms\Vendors\Model\Vendor')
                ->load($this->getVendorId());

            return $this->vendor;
        }
    }

    /**
     * @param int $vendorId
     *
     * @return $this
     */
    public function setVendorId($vendorId)
    {
        return $this->setData('vendor_id', $vendorId);
    }

    /**
     * @return int
     */
    public function getVendorId()
    {
        return $this->getData('vendor_id');
    }

    /**
     * Load banner
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
     * Get Store View Id
     *
     * @return int
     */
    public function getStoreViewId()
    {
        return $this->_storeViewId;
    }

    public function loadByIdentifier($identifier)
    {
        return $this->load($identifier, 'identifier');
    }

    /**
     * Set Store View
     *
     * @param $storeViewId
     * @return $this
     */
    public function setStoreViewId($storeViewId)
    {
        $this->_storeViewId = $storeViewId;
        return $this;
    }

    /**
     * Get Statuses
     *
     * @return array
     */
    public static function getAvailableStatuses()
    {
        return [
            self::STATUS_DISABLE => __('Disabled'),
            self::STATUS_ENABLE => __('Enabled')
        ];
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->getData('status');
    }

    public function setStoreId($storeId)
    {
        $this->_storeId = $storeId;
        return $this;
    }

    public function getStoreId()
    {
        return $this->_storeId;
    }

    /**
     * Get Banner Id
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->getData('banner_id');
    }

    public function getHidePagination()
    {
        return $this->getData('hide_pagination');
    }

    public function getTypePagination()
    {
        return $this->getData('type_pagination');
    }

    public function setTypePagination($type)
    {
        return $this->getData('type_pagination', $type);
    }


    public function getBannerSpeed()
    {
        return $this->getData('speed');
    }

    public function getBannerDelay()
    {
        return $this->getData('delay');
    }

    /**
     * Get status banner
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->getData('status');
    }

    /**
     * {@inheritdoc}
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * Set Banner Id
     *
     * @param mixed $bannerid
     * @return $this
     */
    public function setId($bannerid) {
        return $this->setData('banner_id', $bannerid);
    }


    /**
     * Get Banner Title
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTitle()
    {
        return $this->getData('title');
    }

    /**
     * {@inheritdoc}
     */
    public function setTitle($title)
    {
        return $this->setData(self::TITLE, $title);
    }

    public function getShowItemDescription()
    {
        return $this->getData('show_item_description');
    }

    public function setShowItemDescription($show)
    {
        return $this->getData('show_item_description', $show);
    }

    /**
     * Get banner template
     *
     * @return string
     */
    public function getBannerTemplate()
    {
        return $this->getData('banner_template');
    }


    /**
     * Get Banner Description
     *
     * @return mixed
     */
    public function getBannerDescription()
    {
        return $this->getData('description');
    }

    /**
     * Get Identifier of banner
     *
     * @return mixed
     */
    public function getIdentifier()
    {
        return $this->getData('identifier');
    }

    /**
     * Set identifier for banner
     *
     * @param $identifier
     * @return $this
     */
    public function setIdentifier($identifier) {
        return $this->setData('identifier', $identifier);
    }


    /**
     * Get Store Attributes
     *
     * @return array
     */
    public function getStoreAttributes()
    {
        return array(
            'banner_id',
            'status',
            'filename',
        );
    }

    /**
     * Get effect mode
     *
     * @return mixed
     */
    public function getBannerEffect()
    {
        return $this->getData('effect');
    }

    /**
     *  {@inheritdoc}
     */
    public function getFromDate()
    {
        return $this->getData(BannerInterface::FROM_DATE);
    }

    public function setFromDate($fromdate)
    {
        return $this->setData(BannerInterface::FROM_DATE, $fromdate);
    }

    public function setItemIds($itemIds)
    {
        // TODO: Implement setItemIds() method.
    }

    public function getItemIds()
    {
        // TODO: Implement getItemIds() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getToDate()
    {
        return $this->getData(BannerInterface::TO_DATE);
    }

    /**
     * {@inheritdoc}
     */
    public function setToDate($todate)
    {
        return $this->setData(BannerInterface::TO_DATE, $todate);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensionAttributes()
    {
        return $this->getData(self::EXTENSION_ATTRIBUTES_KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function setExtensionAttributes(BannerExtensionInterface $extensionAttributes)
    {
        return $this->setData(self::EXTENSION_ATTRIBUTES_KEY, $extensionAttributes);
    }
}
