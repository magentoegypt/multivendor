<?php
/**
 * Copyright © Vnecoms. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\Vendors\Block\Account;

use Vnecoms\Vendors\Model\Source\PanelType;
use Magento\Customer\Model\Context;

class Link extends \Magento\Framework\View\Element\Html\Link
{

    /**
     * @var \Vnecoms\Vendors\Helper\Data
     */
    protected $_vendorHelper;

    /**
     * @var \Vnecoms\Vendors\Model\Session
     */
    protected $_vendorSession;

    /**
     * Customer session
     *
     * @var \Magento\Framework\App\Http\Context
     */
    protected $httpContext;

    /**
     * @var bool|\Vnecoms\Vendors\Model\Vendor
     */
    protected $vendor = false;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Vnecoms\Vendors\Helper\Data $vendorHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Vnecoms\Vendors\Helper\Data $vendorHelper,
        \Magento\Framework\App\Http\Context $httpContext,
        \Vnecoms\Vendors\Model\Session $session,
        array $data = []
    ) {
        $this->_vendorHelper = $vendorHelper;
        $this->_vendorSession = $session;
        $this->httpContext = $httpContext;
        if ($this->_vendorSession->getVendor()->getId()) {
            $this->vendor = $this->_vendorSession->getVendor();
        }
        parent::__construct($context, $data);
    }

    /**
     * Is registered vendor
     *
     * @return boolean
     */
    public function getIsRegisteredVendor()
    {
        return $this->httpContext->getValue(Context::CONTEXT_AUTH) && $this->vendor;
    }

    /**
     * @return string
     */
    public function getHref()
    {
        if ($this->_vendorHelper->getPanelType() == PanelType::TYPE_SIMPLE) {
            return $this->getUrl('marketplace/seller/index');
        }

        return $this->_vendorHelper->getUrl('dashboard');
    }

    /**
     * (non-PHPdoc)
     *
     * @see \Magento\Framework\View\Element\Html\Link::_toHtml()
     */
    protected function _toHtml()
    {
        if (!$this->_vendorHelper->moduleEnabled() ||
            !$this->getIsRegisteredVendor() ||
            !$this->vendor ||
            (int) $this->vendor->getStatus() == \Vnecoms\Vendors\Model\Vendor::STATUS_DISABLED
        ) {
            return '';
        }

        return parent::_toHtml();
    }
}
