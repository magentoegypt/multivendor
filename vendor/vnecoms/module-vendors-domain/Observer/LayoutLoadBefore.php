<?php
namespace Vnecoms\VendorsDomain\Observer;

use Magento\Framework\Event\ObserverInterface;
use Vnecoms\VendorsDomain\Model\Config\Source\DomainServerParams;

class LayoutLoadBefore implements ObserverInterface
{
    /**
     * @var \Vnecoms\VendorsDomain\Helper\Data
     */
    protected $helper;
    
    /**
     * @var \Vnecoms\VendorsDomain\Model\DomainFactory
     */
    protected $domainFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    
    /**
     * @param \Vnecoms\VendorsDomain\Helper\Data $helper
     * @param \Vnecoms\VendorsDomain\Model\DomainFactory $domainFactory
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Vnecoms\VendorsDomain\Helper\Data $helper,
        \Vnecoms\VendorsDomain\Model\DomainFactory $domainFactory,
        \Magento\Framework\Registry $registry
    ){
        $this->helper = $helper;
        $this->domainFactory = $domainFactory;
        $this->coreRegistry = $registry;
    }
    
    /**
     * Add layout handle for vendor domain page.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if($this->coreRegistry->registry('vnecoms_is_vendor_domain')){
            $fullActionName = $observer->getFullActionName();
            $layout = $observer->getLayout();
            $layout->getUpdate()->addHandle('vendor_domain_page');
            $layout->getUpdate()->addHandle('vendor_domain_'.$fullActionName);
        }
        return $this;
    }
}
