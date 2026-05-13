<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\Quotation\Block\Adminhtml\Quote\Edit;

use Magento\Framework\Pricing\PriceCurrencyInterface;


class Data extends AbstractQuote
{
    /**
     * Currency factory
     *
     * @var \Magento\Directory\Model\CurrencyFactory
     */
    protected $_currencyFactory;

    /**
     * @var \Magento\Framework\Locale\CurrencyInterface
     */
    protected $_localeCurrency;


    public function __construct(
        \Magento\Directory\Model\CurrencyFactory $currencyFactory,
        \Magento\Framework\Locale\CurrencyInterface $currency,
        \Magento\Backend\Block\Template\Context $context, \Vnecoms\Quotation\Model\Backend\Session $sessionQuote, \Vnecoms\Quotation\Model\Quote $quoteCreate, PriceCurrencyInterface $priceCurrency, \Magento\Framework\Registry $registry, array $data = [])
    {
        $this->_currencyFactory = $currencyFactory;
        $this->_localeCurrency = $currency;
        parent::__construct($context, $sessionQuote, $quoteCreate, $priceCurrency, $registry, $data);
    }


    /**
     * Retrieve curency name by code
     *
     * @param string $code
     * @return string
     */
    public function getCurrencyName($code)
    {
        return $this->_localeCurrency->getCurrency($code)->getName();
    }

    /**
     * Retrieve curency name by code
     *
     * @param string $code
     * @return string
     */
    public function getCurrencySymbol($code)
    {
        $currency = $this->_localeCurrency->getCurrency($code);
        return $currency->getSymbol() ? $currency->getSymbol() : $currency->getShortName();
    }

    /**
     * Retrieve current order currency code
     *
     * @return string
     */
    public function getCurrentCurrencyCode()
    {
        return $this->getStore()->getCurrentCurrencyCode();
    }
}
