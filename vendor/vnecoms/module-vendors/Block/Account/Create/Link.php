<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\Vendors\Block\Account\Create;

use Vnecoms\Vendors\Model\Source\RegisterType;
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
     * Link constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Vnecoms\Vendors\Helper\Data $vendorHelper
     * @param \Vnecoms\Vendors\Model\Session $session
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Vnecoms\Vendors\Helper\Data $vendorHelper,
        \Vnecoms\Vendors\Model\Session $session,
        \Magento\Framework\App\Http\Context $httpContext,
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
        return $this->getIsRegisteredVendor()?$this->getUrl('vendors'):$this->getUrl('marketplace/seller/login');
    }


    /**
     * (non-PHPdoc)
     *
     * @see \Magento\Framework\View\Element\Html\Link::_toHtml()
     */
    protected function _toHtml()
    {
        if (!$this->_vendorHelper->moduleEnabled() ||
            (
                $this->_vendorHelper->getSellerRegisterType() != RegisterType::TYPE_SEPARATED &&
                $this->vendor &&
                $this->vendor->getId()
            ) ||
            (
                $this->vendor &&
                $this->vendor->getId()
            )
        ) {
            return '';
        }
        return parent::_toHtml();
    }
}
