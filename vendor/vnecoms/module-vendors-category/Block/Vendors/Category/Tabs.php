<?php

namespace Vnecoms\VendorsCategory\Block\Vendors\Category;

/**
 * Admin blog category edit form tabs.
 */
class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
    /**
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('category_tabs');
        $this->setDestElementId('category_tab_content');
        $this->setTitle(__('Category Information'));
        $this->setTemplate('Vnecoms_Vendors::widget/tabshoriz.phtml');
    }

    protected function _prepareLayout()
    {
        $this->addTab('main_section', [
            'label' => __('Category Information'),
            'title' => __('Category Information'),
            'content' => $this->getLayout()->createBlock('Vnecoms\VendorsCategory\Block\Vendors\Category\Tab\Main')->toHtml(),
        ]);
        $this->addTab('meta_section', [
            'label' => __('Meta Data'),
            'title' => __('Meta Data'),
            'content' => $this->getLayout()->createBlock('Vnecoms\VendorsCategory\Block\Vendors\Category\Tab\Meta')->toHtml(),
        ]);

        $this->addTab('product_section', [
            'label' => __('Category Products'),
            'title' => __('Category Products'),
            'content' => $this->getLayout()->createBlock('Vnecoms\VendorsCategory\Block\Vendors\Category\Tab\Product', 'category.product.grid')->toHtml(),
        ]);

        return parent::_prepareLayout();
    }
}
