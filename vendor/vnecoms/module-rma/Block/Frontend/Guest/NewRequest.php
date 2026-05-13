<?php

namespace Vnecoms\RMA\Block\Frontend\Guest;

class NewRequest extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;
    /**
     * @var \Vnecoms\RMA\Helper\Config
     */
    protected $_config;
    /**
     * @var \Vnecoms\RMA\Model\ResourceModel\Request\Collection
     */
    protected $_requestCollection;

    /**
     * NewRequest constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Vnecoms\RMA\Helper\Config $config
     * @param \Vnecoms\RMA\Model\ResourceModel\Request\CollectionFactory $requestCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Vnecoms\RMA\Helper\Config $config,
        \Vnecoms\RMA\Model\ResourceModel\Request\CollectionFactory $requestCollection,
        array $data = []
    ) {
        $this->_customerSession     = $customerSession;
        $this->_config              = $config;
        $this->_requestCollection   = $requestCollection->create();
        parent::__construct($context, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->pageConfig->getTitle()->set(__('Request RMA'));
    }

    public function getContinueUrl()
    {
        return $this->getUrl('*/*/postrma');
    }
    public function getSubmitUrl()
    {
        return $this->getUrl('*/*/save');
    }
    /**
     * @return \Vnecoms\RMA\Helper\Config
     */
    public function getConfig()
    {
        return $this->_config;
    }
}
