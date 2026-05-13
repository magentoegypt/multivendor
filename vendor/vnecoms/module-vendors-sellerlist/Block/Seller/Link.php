<?php
/**
 *
 * Created by Vnecoms Core Team.
 *
 * @category  Vnecoms
 * @package   Vnecoms_ModuleName
 * @author    Vnecoms
 * @created_by mrtuvn
 * @date: 03/05/2017
 * @time: 10:36
 * @copyright Copyright (c) 2012-2017 Vnecoms
 * @license   https://www.vnecoms.com
 */


namespace Vnecoms\VendorsSellerList\Block\Seller;

use Vnecoms\Vendors\Model\Source\RegisterType;

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

    /** @var \Vnecoms\VendorsSellerList\Helper\Data  */
    protected $sellerListHelper;

    /**
     * Link constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Vnecoms\Vendors\Helper\Data $vendorHelper
     * @param \Vnecoms\Vendors\Model\Session $session
     * @param \Vnecoms\VendorsSellerList\Helper\Data $sellerListHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Vnecoms\Vendors\Helper\Data $vendorHelper,
        \Vnecoms\Vendors\Model\Session $session,
        \Vnecoms\VendorsSellerList\Helper\Data $sellerListHelper,
        array $data = []
    ) {
        $this->_vendorHelper = $vendorHelper;
        $this->_vendorSession = $session;
        $this->sellerListHelper = $sellerListHelper;
        parent::__construct($context, $data);
    }

    /**
     * Is registered vendor
     *
     * @return boolean
     */
    public function getIsRegisteredVendor(){
        return $this->_vendorSession->isLoggedIn() && $this->_vendorSession->getVendor()->getId();
    }

    /**
     * @return string
     */
    public function getHref()
    {
        return $this->getUrl('sellerlist');
    }


    /**
     * (non-PHPdoc)
     *
     * @see \Magento\Framework\View\Element\Html\Link::_toHtml()
     */
    protected function _toHtml(){
        return parent::_toHtml();
    }
}
