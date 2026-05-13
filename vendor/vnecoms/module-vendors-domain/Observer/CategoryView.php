<?php
namespace Vnecoms\VendorsDomain\Observer;

use Magento\Framework\Event\ObserverInterface;

class CategoryView implements ObserverInterface
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;
    
    /**
     * @param \Magento\Framework\Registry $coreRegistry
     */
    public function __construct(
        \Magento\Framework\Registry $coreRegistry
    ) {
        $this->coreRegistry = $coreRegistry;
    }
    /**
     * Add restriction to product page.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $category = $observer->getCategory();
        if(
            $this->coreRegistry->registry('vnecoms_is_vendor_domain')
        ) {
            throw new \Magento\Framework\Exception\LocalizedException(__('This page does not exist for vendor'));
        }
    }
}
