<?php
namespace Vnecoms\VendorsCoupon\Block\Vendors\Coupon\Edit;

use Magento\Framework\Module\Manager;
/**
 * Class Tabs Grid
 * 
 * @category Vnecoms
 * @package  Vnecoms_BannerManager
 * @module   BannerManager
 * @author   Vnecoms Developer Team
 */
class Tabs extends \Vnecoms\Vendors\Block\Vendors\Widget\Tabs
{
    /**
     * parent constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('coupon_edit_tabs'); //name in layout tab declare xml
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Coupon Information'));
    }

    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        return parent::_prepareLayout();

    }

}

