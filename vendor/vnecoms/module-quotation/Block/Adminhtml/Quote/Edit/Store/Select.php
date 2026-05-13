<?php
/**
 * Copyright (c) 2017 Vnecoms Co ltd. All rights reserved.
 */
namespace Vnecoms\Quotation\Block\Adminhtml\Quote\Edit\Store;

/**
 * Adminhtml quote create select store block
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Select extends \Magento\Backend\Block\Store\Switcher
{
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('sc_store_select');
    }
}
