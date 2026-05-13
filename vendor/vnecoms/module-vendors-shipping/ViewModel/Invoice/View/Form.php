<?php
namespace Vnecoms\VendorsShipping\ViewModel\Invoice\View;

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
     * @var \Vnecoms\VendorsSales\Model\ResourceModel\Order\Invoice\Collection
     */
    protected $vendorInvoice;

    /**
     * Form constructor.
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Sales\Helper\Admin $admin
     * @param \Vnecoms\VendorsSales\Model\ResourceModel\Order\Invoice\Collection $vendorInvoice
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Helper\Admin $admin,
        \Vnecoms\VendorsSales\Model\ResourceModel\Order\Invoice\CollectionFactory $vendorInvoice
    ) {
        $this->_coreRegistry = $registry;
        $this->_adminHelper = $admin;
        $this->vendorInvoice = $vendorInvoice;
    }

    /**
     * @return mixed
     */
    public function getInvoice() {
        return $this->_coreRegistry->registry('current_invoice');
    }


    /**
     * @return array
     */
    public function getVendorOrders()
    {
        $vendorOrders = [];
        $vendorInvoices = $this->vendorInvoice->create()
            ->addFieldToFilter('invoice_id',$this->getInvoice()->getId());

        foreach ($vendorInvoices as $vendorInvoice) {
            $vendorOrders[] = $vendorInvoice->getOrder();
        }

        return $vendorOrders;
    }


    /**
     * @param $object
     * @param $code
     * @param bool $strong
     * @param string $separator
     * @return string
     */
    public function displayPriceAttribute($object, $code, $strong = false, $separator = '<br/>')
    {
        return $this->_adminHelper->displayPriceAttribute($object, $code, $strong, $separator);
    }

}
