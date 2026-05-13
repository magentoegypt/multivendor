<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\Quotation\Block\Adminhtml\Quote\Edit;


class Search extends AbstractQuote
{
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('quote_search');
    }

    /**
     * Get header text
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        return __('Please select products');
    }

    /**
     * Get buttons html
     *
     * @return string
     */
    public function getButtonsHtml()
    {
        $addButtonData = [
            'label' => __('Add Selected Product(s) to Quote'),
            'onclick' => 'quote.productGridAddSelected()',
            'class' => 'action-add action-secondary',
        ];
        return $this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Button'
        )->setData(
            $addButtonData
        )->toHtml();
    }

    /**
     * Get header css class
     *
     * @return string
     */
    public function getHeaderCssClass()
    {
        return 'head-catalog-product';
    }
}
