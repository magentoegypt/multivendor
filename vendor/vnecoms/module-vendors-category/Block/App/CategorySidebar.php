<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCategory\Block\App;

use Vnecoms\VendorsCategory\Model\Category;
use Magento\Framework\Data\Collection;
use Magento\Framework\Data\TreeFactory;
use Magento\Framework\Data\Tree\Node;
use Magento\Framework\Data\Tree\NodeFactory;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\View\Element\Template;

/**
 * Plugin for top menu block.
 */
class CategorySidebar extends \Vnecoms\VendorsCategory\Block\Menu
{
    /**
     * Get block cache life time.
     *
     * @return int
     */
    protected function getCacheLifetime()
    {
        return false;
    }

}
