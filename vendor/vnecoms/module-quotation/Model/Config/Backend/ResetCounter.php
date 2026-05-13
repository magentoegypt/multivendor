<?php

namespace Vnecoms\Quotation\Model\Config\Backend;

use Vnecoms\Quotation\Model\SequenceManager;

class ResetCounter extends \Magento\Framework\App\Config\Value
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var Manager
     */
    protected $_sequenceManager;

    /**
     * Constructor.
     * 
     * @param \Magento\Framework\Model\Context                        $context
     * @param \Magento\Framework\Registry                             $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface      $config
     * @param \Magento\Framework\App\Cache\TypeListInterface          $cacheTypeList
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb           $resourceCollection
     * @param \Magento\Store\Model\StoreManagerInterface              $storeManager
     * @param array                                                   $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        SequenceManager $sequenceManager,
        array $data = []
    ) {
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
        $this->_storeManager = $storeManager;
        $this->_sequenceManager = $sequenceManager;
    }

    public function beforeSave()
    {
        switch ($this->getScope()) {
            case 'default':
                $storeId = $this->getScopeId();
                break;
            case 'websites':
                $storeId = $this->_storeManager->getWebsite($this->getScopeId())->getDefaultStore()->getId();
                break;
            case 'stores':
                $storeId = $this->getScopeId();
                break;
        }
        /*Reset counter*/
        $this->_sequenceManager->getSequence($storeId, $storeId)->resetCounter();

        $this->setValue(null);
    }
}
