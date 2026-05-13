<?php

namespace Vnecoms\VendorsCategory\Plugin\Model\Layer\Filter;

class Item
{
    protected $registry;

    public function getRegistry()
    {
        if (!$this->registry) {
            $this->registry = \Magento\Framework\App\ObjectManager::getInstance()
                ->get('Magento\Framework\Registry');
        }

        return $this->registry;
    }

    public function aroundGetUrl(\Magento\Catalog\Model\Layer\Filter\Item $item, $proceed)
    {
        //only effect on vendor category page
        $result = $proceed;
        return 'test';
    }

    public function aroundGetRemoveUrl(\Magento\Catalog\Model\Layer\Filter\Item $item, $proceed)
    {
        $result = $proceed;
        return 'test';
    }

    public function aroundGetClearLinkUrl(\Magento\Catalog\Model\Layer\Filter\Item $item, $proceed)
    {
        $result = $proceed;
        return 'test';
    }
}
