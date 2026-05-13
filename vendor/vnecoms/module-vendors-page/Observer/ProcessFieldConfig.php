<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsPage\Observer;

use Magento\Framework\Event\ObserverInterface;
use Vnecoms\VendorsConfig\Helper\Data;

class ProcessFieldConfig implements ObserverInterface
{
    /**
     * Add multiple vendor order row for each vendor.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {

        $transport = $observer->getTransport();
        $fieldset = $transport->getFieldset();

        $arrays = [
            "show_about"=>"page_general_description",
            "show_shipping"=>"page_general_shipping_policy",
            "show_refund"=>"page_general_refund_policy"
        ];

        $config = \Magento\Framework\App\ObjectManager::getInstance()->get(
            'Magento\Framework\App\Config\ScopeConfigInterface'
        );

        foreach ($fieldset->getElements() as $field) {
            foreach ($arrays as $key => $value) {
                if ($field->getHtmlId() == $value) {
                    $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
                    $configVal = $config->getValue("vendors/vendorspage/".$key, $storeScope);
                    if (!$configVal) {
                        $field->setIsRemoved(true);
                    }
                }
            }
        }

        return $this;
    }
}
