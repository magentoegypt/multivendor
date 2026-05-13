<?php
/**
 * Copyright © Vnecoms. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\Vendors\Block\Vendors\Page;

/**
 * Vendor Title Block
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Title extends \Vnecoms\Vendors\Block\Vendors\AbstractBlock
{
    /**
     * Get short title
     * @return string
     */
    public function getTitle()
    {
        $title = $this->pageConfig->getTitle()->getShort();
        return isset($title) ? __($title) : '';
    }
}
