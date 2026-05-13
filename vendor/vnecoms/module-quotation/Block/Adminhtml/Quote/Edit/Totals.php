<?php

namespace Vnecoms\Quotation\Block\Adminhtml\Quote\Edit;

use Magento\Framework\Pricing\PriceCurrencyInterface;

class Totals extends AbstractQuote
{
    public function __construct(\Magento\Backend\Block\Template\Context $context, \Vnecoms\Quotation\Model\Backend\Session $sessionQuote, \Vnecoms\Quotation\Model\Quote $quoteCreate, PriceCurrencyInterface $priceCurrency, \Magento\Framework\Registry $registry, array $data = [])
    {
        parent::__construct($context, $sessionQuote, $quoteCreate, $priceCurrency, $registry, $data);
    }


}