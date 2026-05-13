<?php

namespace Vnecoms\Quotation\Block\Quote;

use Magento\Customer\Model\Context;
use Magento\Framework\View\Element\Template;

class View extends \Magento\Framework\View\Element\Template
{
    protected $_template = 'customer/quote/view.phtml';

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    public function __construct
    (
        Template\Context $context,
        \Magento\Customer\Model\Session $session,
        \Magento\Framework\Registry $registry,
        array $data = []
    )
    {
        parent::__construct($context, $data);

        $this->_customerSession = $session;
        $this->_coreRegistry = $registry;
        $this->_isScopePrivate = true;
    }

    /**
     * Retrieve current order model instance
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getQuote()
    {
        return $this->_coreRegistry->registry('current_quote');
    }
}