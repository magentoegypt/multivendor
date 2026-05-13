<?php

namespace Vnecoms\VendorsCategory\Plugin\Block\Navigation;

class State
{

    public function aroundGetClearUrl(\Vnecoms\VendorsLayerNavigation\Block\Navigation\State $state, $proceed)
    {
        if (\Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\Registry')
            ->registry('current_vendor_category')) {
        }
    }
}
