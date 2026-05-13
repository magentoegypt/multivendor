<?php

namespace Vnecoms\RMA\Block\Frontend\Customer;

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
        array $data = []
    ) {
        $this->_customerSession     = $customerSession;
        $this->_config              = $config;
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
