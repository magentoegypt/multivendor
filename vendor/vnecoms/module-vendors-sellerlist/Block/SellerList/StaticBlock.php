<?php

namespace Vnecoms\VendorsSellerList\Block\SellerList;

use Magento\Framework\App\ObjectManager;

class StaticBlock extends \Magento\Cms\Block\Block
{
    public function getBlockId(){
        $helper = ObjectManager::getInstance()->get('Vnecoms\VendorsSellerList\Helper\Data');
        return $this->getBlockPosition() == 'top' ?$helper->getTopStaticBlockId() : $helper->getBottomStaticBlockId();
    }
}
