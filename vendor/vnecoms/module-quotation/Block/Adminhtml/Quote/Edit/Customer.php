<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\Quotation\Block\Adminhtml\Quote\Edit;

/**
 * Adminhtml quote create block
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Customer extends AbstractQuote
{
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('quotation_quote_create_customer');
    }

    /**
     * Get header text
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        return __('Please select a customer');
    }

    /**
     * Get buttons html
     *
     * @return string
     */
    public function getButtonsHtml()
    {
            $addButtonData = [
                'label' => __('Quote With Guest'),
                'onclick' => 'quote.setCustomerId(false);',
                'class' => 'primary',
            ];
            return $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button')
                ->setData($addButtonData)
                ->toHtml();
    }
}
