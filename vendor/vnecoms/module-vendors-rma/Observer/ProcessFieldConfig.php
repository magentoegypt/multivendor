<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsRMA\Observer;

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

        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $module = $object_manager->get('Magento\Framework\Module\Manager');


        if ($fieldset->getHtmlId() == "rma_rmapolicy") {

            foreach ($fieldset->getElements() as $field) {
                if (!$module->isEnabled("Vnecoms_VendorsCms"))
                    $field->setIsRemoved(true);
            }
        }
        return $this;
    }
}
