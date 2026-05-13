<?php

namespace Vnecoms\RMA\Model\System\Config\Backend;

use Vnecoms\RMA\Model\SequenceManager;

class ResetCounter extends \Magento\Framework\App\Config\Value
{

    /**
     * @var Manager
     */
    protected $_sequenceManager;

    /**
     * Constructor
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        \Vnecoms\RMA\Model\SequenceManager $sequenceManager,
        array $data = []
    ) {
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
        $this->_sequenceManager = $sequenceManager;
    }

    public function beforeSave()
    {
        $this->_sequenceManager->getSequence()->resetCounter();
        $this->setValue(null);
    }
}
