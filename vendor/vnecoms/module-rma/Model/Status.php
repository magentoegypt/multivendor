<?php

namespace Vnecoms\RMA\Model;

use Magento\Framework\Model\AbstractModel;
use Vnecoms\RMA\Api\Data\StatusInterface;

class Status extends AbstractModel implements StatusInterface
{
    /**
     * @var \Vnecoms\RMA\Model\Request\Status\Store
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
     * @param ResourceModel\Status $resource
     * @param Request\Reason\Store $store
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Registry $registry,
        \Vnecoms\RMA\Model\ResourceModel\Status $resource,
        \Vnecoms\RMA\Model\Request\Status\Store $store,
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
        $this->_init('Vnecoms\RMA\Model\ResourceModel\Status');
    }

    /**
     * get Label Status by store id
     * @return $title
     */
    public function getLabelByStoreId($storeId = null)
    {
        if (!$storeId) {
            return $this->getTitle();
        }
        $statuStores = \Magento\Framework\App\ObjectManager::getInstance()->get(
            'Vnecoms\RMA\Model\Request\Status\Store'
        )->getCollection()
        ->addFieldToFilter("status_id", $this->getId());
        if (!$statuStores || !count($statuStores)) {
            return $this->getTitle() ? $this->getTitle()  : "N/A";
        } ;
        foreach ($statuStores as $store) {
            if ($store->getStoreId() == $storeId) {
                return $store->getTitle();
            }
        }
        return $this->getTitle() ? $this->getTitle()  : "N/A";
        ;
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
        foreach ($collections as $status) {
            $data[] = [
                "label"=>$status->getLabelByStoreId($this->_storeManager->getStore()->getId())
                ,"value"=>$status->getId()
            ];
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
        foreach ($collections as $status) {
            $data[$status->getId()] = $status->getLabelByStoreId($this->_storeManager->getStore()->getId());
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
        return $this->getData(self::STATUS_ID);
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
     * Get code
     *
     * @return string|null
     */
    public function getCode()
    {
        return $this->getData(self::CODE);
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
     * @return \Vnecoms\RMA\Api\Data\StatusInterface
     */
    public function setId($id)
    {
        return $this->setData(self::STATUS_ID, $id);
    }


    /**
     * Set title
     *
     * @param string $title
     * @return \Vnecoms\RMA\Api\Data\StatusInterface
     */
    public function setTitle($title)
    {
        return $this->setData(self::TITLE, $title);
    }

    /**
     * Set Code
     *
     * @param string $code
     * @return \Vnecoms\RMA\Api\Data\StatusInterface
     */
    public function setCode($code)
    {
        return $this->setData(self::CODE, $code);
    }

    /**
     * Set Is Main
     *
     * @param string $isMain
     * @return \Vnecoms\RMA\Api\Data\StatusInterface
     */
    public function setIsMain($isMain)
    {
        return $this->setData(self::IS_MAIN, $isMain);
    }

    /**
     * Set status
     *
     * @param string $status
     * @return  \Vnecoms\RMA\Api\Data\StatusInterface
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * get All Email template of status
     * @return array
     */
    public function getEmailTemplates($storeId = null)
    {

        if (!$this->hasStoreTemplates()) {
            $templates = $this->_getResource()->getStoreTemplates($this->getId(), $storeId);
            $this->setStoreTemplates($templates);
        }
        return $this->_getData('store_templates');
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
