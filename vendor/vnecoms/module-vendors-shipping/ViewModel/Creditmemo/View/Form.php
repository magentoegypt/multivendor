<?php
namespace Vnecoms\VendorsShipping\ViewModel\Creditmemo\View;

class Form implements \Magento\Framework\View\Element\Block\ArgumentInterface
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * Admin helper
     *
     * @var \Magento\Sales\Helper\Admin
     */
    protected $_adminHelper;

    /**
     * Form constructor.
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Sales\Helper\Admin $admin
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Helper\Admin $admin
    ) {
        $this->_coreRegistry = $registry;
        $this->_adminHelper = $admin;
    }

    /**
     * @return \Vnecoms\Vendors\Model\Order
     */
    public function getVendorOrder()
    {
        return $this->_coreRegistry->registry('vendor_order');
    }

    /**
     * Get price data object
     *
     * @return Order|mixed
     */
    public function getPriceDataObject()
    {
        return $this->getVendorOrder();
    }

    /**
     * Display price attribute
     *
     * @param string $code
     * @param bool $strong
     * @param string $separator
     * @return string
     */
    public function displayPriceAttribute($code, $strong = false, $separator = '<br/>')
    {
        return $this->_adminHelper->displayPriceAttribute($this->getPriceDataObject(), $code, $strong, $separator);
    }

}
