<?php

namespace Vnecoms\RMA\Model;

use Magento\Framework\Model\AbstractModel;
use Vnecoms\RMA\Api\Data\ReasonInterface;
use Vnecoms\RMA\Ui\Component\Listing\Columns\Reason\Shipping;

/**
 * Shopping Cart Rule data model
 *
 * @method \Vnecoms\RMA\Model\ResourceModel\Reason _getResource()
 *
 */

class Reason extends AbstractModel implements ReasonInterface
{

    /**
     * @var \Vnecoms\RMA\Model\Request\Reason\Store
     */
    protected $_storeModel;

    /**
     * store manager
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Reason constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param ResourceModel\Reason $resource
     * @param Request\Reason\Store $store
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Vnecoms\RMA\Model\ResourceModel\Reason $resource,
        \Vnecoms\RMA\Model\Request\Reason\Store $store,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_storeManager = $storeManager;
        $this->_storeModel = $store;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Define resource model
     */
    protected function _construct()
    {
        $this->_init('Vnecoms\RMA\Model\ResourceModel\Reason');
    }

    /**
     * get Label Reason by store id
     */
    public function getLabelByStoreId($storeId = null)
    {
        if (!$storeId) {
            return $this->getTitle();
        }
        $statuStores = \Magento\Framework\App\ObjectManager::getInstance()->get(
            'Vnecoms\RMA\Model\Request\Reason\Store'
        )->getCollection()
            ->addFieldToFilter("reason_id", $this->getId());
        if (!$statuStores || !count($statuStores)) {
            return $this->getTitle()? $this->getTitle()  : "N/A";
        }
        foreach ($statuStores as $store) {
            if ($store->getStoreId() == $storeId) {
                return $store->getTitle();
            }
        }
        return $this->getTitle() ? $this->getTitle()  : "N/A";
    }

    /**
     * @return bool|\Magento\Framework\Phrase
     */
    public function getWhoPayForShip()
    {
        switch ($this->getData('who_pay_shipping')) {
            case Shipping::DO_NOT_SHOW:
                return false;
            case Shipping::STORE_OWNER:
                return __("Store Owner");
            case Shipping::CUSTOMER:
                return __("Customer");
        }
        return false;
    }

    /**
     * Set if not yet and retrieve rule store labels
     *
     * @return array
     */
    public function getStoreLabels()
    {
        if (!$this->hasStoreLabels()) {
            $labels = $this->_getResource()->getStoreLabels($this->getId());
            $this->setStoreLabels($labels);
        }

        return $this->_getData('store_labels');
    }

    /**
     * get All options array reason
     * @return array
     */
    public function toOptionArray()
    {
        $data = [];
        $collections = $this->getCollection()
            ->addFieldToFilter("status", \Vnecoms\RMA\Model\Source\Status::STATUS_ENABLED);
        foreach ($collections as $reason) {
            $data[] = ["label"=>$reason->getTitle(),"value"=>$reason->getId()];
        }
        return $data;
    }

    /**
     * get All options array reason
     * @return array
     */
    public function getOptionArray()
    {
        $data = [];
        $collections = $this->getCollection()
            ->addFieldToFilter("status", \Vnecoms\RMA\Model\Source\Status::STATUS_ENABLED);
        foreach ($collections as $reason) {
            $data[$reason->getId()] = $reason->getLabelByStoreId($this->_storeManager->getStore()->getId());
        }
        return $data;
    }

    /**
     * Get ID
     *
     * @return int|null
     */
    public function getId()
    {
        return $this->getData(self::REASON_ID);
    }


    /**
     * Get title
     *
     * @return string|null
     */
    public function getTitle()
    {
        return $this->getData(self::TITLE);
    }

    /**
     * Get content template
     *
     * @return string|null
     */
    public function getIsMain()
    {
        return $this->getData(self::IS_MAIN);
    }

    /**
     * Get status
     *
     * @return string|null
     */
    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    /**
     * Set ID
     *
     * @param int $id
     * @return \Vnecoms\RMA\Api\Data\ReasonInterface
     */
    public function setId($id)
    {
        return $this->setData(self::REASON_ID, $id);
    }


    /**
     * Set title
     *
     * @param string $title
     * @return \Vnecoms\RMA\Api\Data\ReasonInterface
     */
    public function setTitle($title)
    {
        return $this->setData(self::TITLE, $title);
    }

    /**
     * Set Is Main
     *
     * @param string $isMain
     * @return \Vnecoms\RMA\Api\Data\ReasonInterface
     */
    public function setIsMain($isMain)
    {
        return $this->setData(self::IS_MAIN, $isMain);
    }

    /**
     * Set status
     *
     * @param string $status
     * @return  \Vnecoms\RMA\Api\Data\ReasonInterface
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }


    /**
     * Set display label
     *
     * @param \\Vnecoms\RMA\Api\Data\ReasonInterface[]|null $storeLabels
     * @return $this
     */
    public function setStoreLabels(array $storeLabels = null)
    {
        return $this->setData("store_labels", $storeLabels);
    }
}
