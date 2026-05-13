<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsRMA\Block\Vendor\Order\View\Tab;

/**
 * Order HelpDesk grid
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Rma extends \Magento\Framework\View\Element\Text\ListText implements
    \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('RMA');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('RMA');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }
}
