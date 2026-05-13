<?php

namespace Vnecoms\VendorsCms\Observer\Frontend;

use Magento\Framework\Event\ObserverInterface;

class UpdateLayerBlockAfter implements ObserverInterface
{
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        return;
        // TODO: Implement execute() method.
        $action = $observer->getEvent()->getData('full_action_name');
        $layout = $observer->getEvent()->getData('layout');

        $layout->unsetElement('store.menu');
        $layout->unsetElement('footer');

       // $left = $layout->getBlock('div.sidebar.additional');
        $leftChildren = $layout->getChildBlocks('div.sidebar.additional');
        foreach ($leftChildren as $key => $value) {
            // var_dump($key);
            if (substr($key, 0, 6) == 'vendor') {
                continue;
            }
            $layout->unsetChild('div.sidebar.additional', $key);
        }
    }
}
