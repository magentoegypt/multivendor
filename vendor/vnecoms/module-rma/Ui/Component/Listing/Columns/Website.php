<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Ui\Component\Listing\Columns;

use Magento\Store\Ui\Component\Listing\Column\Store\Options as StoreOptions;

/**
 * Store Options for Cms Pages and Blocks
 */
class Website extends StoreOptions
{
    /**
     * All Store Views value
     */
    const ALL_STORE_VIEWS = '0';

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        if ($this->options !== null) {
            return $this->options;
        }

        $websiteCollection = $this->systemStore->getWebsiteCollection();
        foreach ($websiteCollection as $website) {
            $this->options[] = ["label"=>$website->getName(),"value"=>$website->getId()];
        }
        return $this->options;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function getOptionArray()
    {
        $data = [];
        $websiteCollection = $this->systemStore->getWebsiteCollection();
        foreach ($websiteCollection as $website) {
            $data[$website->getId()] = $website->getName();
        }
        return $data;
    }
}
