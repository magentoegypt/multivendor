<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsCustomTheme\Observer;

use Magento\Framework\Event\ObserverInterface;

class AfterSaveVendor implements ObserverInterface
{
    /**
     * @var  \Vnecoms\VendorsConfig\Model\ConfigFactory
     */
    protected $_configFactory;
    
    /**
     * 
     * @param \Vnecoms\VendorsConfig\Model\ConfigFactory $configFactory
     */
    public function __construct(
        \Vnecoms\VendorsConfig\Model\ConfigFactory $configFactory
    ) {
        $this->_configFactory = $configFactory;
    }

    /**
     * Add the notification if there are any vendor awaiting for approval.
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $vendor = $observer->getVendor();
        $customTheme = $vendor->getData('vendor_custom_theme');
        if($customTheme){
            $path = 'custom_theme/general/theme';
            $config = $this->_configFactory->create();
            $configCollection = $config->getCollection()->addPathFilter($path, $vendor->getId());
            if($configCollection->count()){
                $configCollection->getFirstItem()->setValue($customTheme)->save();
            }else{
                $config->setData([
                    'vendor_id' => $vendor->getId(),
                    'path'      => $path,
                    'value'     => $customTheme,
                ])->save();
            }
        }
    }
}
