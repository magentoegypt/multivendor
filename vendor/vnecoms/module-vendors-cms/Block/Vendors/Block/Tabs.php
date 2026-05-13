<?php

namespace Vnecoms\VendorsCms\Block\Vendors\Block;

/**
 * Class Tabs Grid.
 *
 * @category Vnecoms
 * @module   VendorsCms
 *
 * @author   Vnecoms Developer Team
 */
class Tabs extends \Vnecoms\Vendors\Block\Vendors\Widget\Tabs
{
    /**
     * parent constructor.
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('vendor_block_edit_tabs'); //name in layout tab declare xml
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Block Information'));
    }

    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        $this->addTab('main_section', [
            'label' => __('Block Information'),
            'title' => __('Block Information'),
            'content' => $this->getLayout()->createBlock('Vnecoms\VendorsCms\Block\Vendors\Block\Tab\Main')->toHtml(),
        ]);

        return parent::_prepareLayout();
    }
}
