<?php
/**
 * Copyright (c) 2017 Vnecoms Co ltd. All rights reserved.
 */
namespace Vnecoms\Quotation\Block\Adminhtml\Quote;


class Js extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->registry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * @return \Vnecoms\Quotation\Model\Quote
     */
    public function getQuote()
    {
        return $this->registry->registry('current_quote');
    }

    /**
     * get configure product url
     *
     * @return string
     */
    public function getConfigureProductUrl()
    {
        return $this->getUrl('*/quote_create/configureProductToAdd', ['quote_id' => $this->getQuote()->getId()]);
    }
    
    /**
     * get configure quote items url
     *
     * @return string
     */
    public function getConfigureQuoteItemsUrl()
    {
        return $this->getUrl('*/quote_create/configureQuoteItems', ['quote_id' => $this->getQuote()->getId()]);
    }
    
}
