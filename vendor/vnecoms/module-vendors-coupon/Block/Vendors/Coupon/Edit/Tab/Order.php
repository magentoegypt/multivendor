<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsCoupon\Block\Vendors\Coupon\Edit\Tab;

use Vnecoms\Vendors\Block\Vendors\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;

/**
 * Customer Credit transactions grid
 * @SuppressWarnings(PHPMD.DepthOfInheritance)
 */
class Order extends Generic implements TabInterface
{
    /**
     * @var string
     */
    protected $_template = 'Vnecoms_VendorsCoupon::coupon/edit/tab/order.phtml';

    /**
     * Prepare form
     *
     * @param void
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareForm()
    {
        parent::_prepareForm();
    }
    /**
     * Prepare content for tab
     *
     * @return \Magento\Framework\Phrase
     * @codeCoverageIgnore
     */
    public function getTabLabel()
    {
        return __('Orders');
    }
    
    /**
     * Prepare title for tab
     *
     * @return \Magento\Framework\Phrase
     * @codeCoverageIgnore
     */
    public function getTabTitle()
    {
        return __('Orders');
    }
    
    /**
     * Returns status flag about this tab can be showed or not
     *
     * @return bool
     * @codeCoverageIgnore
     */
    public function canShowTab()
    {
        return true;
    }
    
    /**
     * Returns status flag about this tab hidden or not
     *
     * @return bool
     * @codeCoverageIgnore
     */
    public function isHidden()
    {
        return false;
    }
}
