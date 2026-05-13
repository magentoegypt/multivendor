<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsSearch\Observer;

use Magento\Framework\Event\ObserverInterface;

class PreDispatchSearchResult implements ObserverInterface
{
    /**
     * @var \Vnecoms\VendorsPage\Helper\Data
     */
    protected $_coreRegistry;

    public function __construct(
        \Magento\Framework\Registry $registry
    ) {
        $this->_coreRegistry = $registry;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        // TODO: Implement execute() method.
        $this->_coreRegistry->register('search_result_page', true);
    }
}
