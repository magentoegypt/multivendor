<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\Credit\Block\Customer\Account;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Customer\Block\Account\SortLinkInterface;

/**
 * Shopping cart item render block for configurable products.
 */
class Link extends \Magento\Framework\View\Element\Html\Link\Current implements SortLinkInterface
{
    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $priceCurrency;
    
    /**
     * @var \Vnecoms\Credit\Model\Credit
     */
    protected $creditAccount;

    /**
     * @var \Vnecoms\Credit\Helper\Data
     */
    protected $_creditHelper;

    /**
     * Link constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\App\DefaultPathInterface $defaultPath
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Vnecoms\Credit\Model\CreditFactory $creditAccountFactory
     * @param PriceCurrencyInterface $priceCurrency
     * @param \Vnecoms\Credit\Helper\Data $creditHelper
     * @param array $data
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\App\DefaultPathInterface $defaultPath,
        \Magento\Customer\Model\Session $customerSession,
        \Vnecoms\Credit\Model\CreditFactory $creditAccountFactory,
        PriceCurrencyInterface $priceCurrency,
        \Vnecoms\Credit\Helper\Data $creditHelper,
        array $data = []
    ) {
        $this->priceCurrency = $priceCurrency;
        $this->_creditHelper = $creditHelper;
        $this->creditAccount = $creditAccountFactory->create();
        $this->creditAccount->loadByCustomerId($customerSession->getId());
        
        parent::__construct($context, $defaultPath, $data);
    }
    
    /**
     * Get link label
     * @return string
     */
    public function getLabel()
    {
        return __("My Credit (%1)", "<strong class=\"credit-balance\">".$this->formatBasePrice($this->creditAccount->getCredit(), false)."</strong>");
    }
   
   /**
    * Format price
    * @param string $price
    */
    public function formatPrice($price = 0)
    {
        $price = $this->priceCurrency->convert($price);
        return $this->priceCurrency->format($price, false);
    }
   
   /**
    * Format base currency
    * @param number $price
    */
    public function formatBasePrice($price = 0)
    {
        return $this->_storeManager->getStore()->getBaseCurrency()->formatPrecision($price, 2, [], false);
    }

    /**
     * {@inheritdoc}
     * @since 101.0.0
     */
    public function getSortOrder()
    {
        return $this->getData(self::SORT_ORDER);
    }


    /**
    * Disable escape html for this block.
    * @SuppressWarnings(PHPMD.UnusedFormalParameter)
    */
    public function escapeHtml($data, $allowedTags = null)
    {
        return $data;
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    protected function _toHtml()
    {
        if (!$this->_creditHelper->isDisplayMyCreditOnTopLinks()) {
            return '';
        }
        return parent::_toHtml();
    }
}
