<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsPage\Model\Layer;

class Resolver extends \Magento\Catalog\Model\Layer\Resolver
{
    const VENDOR_PAGE_LAYER = 'vendor_page';


    /**
     * Get current Catalog Layer
     *
     * @return \Magento\Catalog\Model\Layer
     */
    public function get()
    {
        if (!isset($this->layer)) {
            $this->layer = $this->objectManager->create($this->layersPool[self::VENDOR_PAGE_LAYER]);
        }
        return $this->layer;
    }
}
