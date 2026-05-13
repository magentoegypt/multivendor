<?php
namespace Vnecoms\VendorsDomain\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\ObjectManager;

class CmsPage implements ObserverInterface
{
    /**
     * @var \Vnecoms\VendorsDomain\Helper\Data
     */
    protected $helper;
    
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
        \Magento\Framework\Registry $registry
    ){
        $this->helper = $helper;
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
        if(!$this->coreRegistry->registry('vnecoms_is_vendor_domain')) return;
        $request = $observer->getRequest();
        $request->setParam('page_id','');
    }
}
