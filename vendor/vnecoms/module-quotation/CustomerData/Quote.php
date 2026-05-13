<?php

namespace Vnecoms\Quotation\CustomerData;

use Magento\Customer\CustomerData\SectionSourceInterface;
use Vnecoms\Quotation\Model\Item;

/**
 * Quote source
 */
class Quote extends \Magento\Framework\DataObject implements SectionSourceInterface
{
    /**
     * @var \Vnecoms\Quotation\Model\Session
     */
    protected $session;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Url
     */
    protected $catalogUrl;

    /**
     * @var \Vnecoms\Quotation\Model\Quote|null
     */
    protected $quote = null;

    /**
     * @var \Vnecoms\Quotation\Helper\Data
     */
    protected $helper;

    /**
     * @var int|float
     */
    protected $summeryCount;

    /**
     * @var \Magento\Framework\View\LayoutInterface
     */
    protected $layout;

    /**
     * @var ItemData
     */
    protected $itemData;

    public function __construct(
        \Vnecoms\Quotation\Model\Session $session,
        \Magento\Catalog\Model\ResourceModel\Url $catalogUrl,
        \Vnecoms\Quotation\Helper\Data $helper,
        \Magento\Framework\View\LayoutInterface $layout,
        ItemData $itemData,
        array $data = []
    ) {
        parent::__construct($data);
        $this->session = $session;
        $this->catalogUrl = $catalogUrl;
       // $this->quote = $quote;
        $this->helper = $helper;
        $this->layout = $layout;
        $this->itemData = $itemData;
    }

    /**
     * Data quote stored in storage frontend
     *
     * {@inheritdoc}
     */
    public function getSectionData()
    {
        $totals = $this->getQuote()->getTotals();
        return [
            'summary_count' => (int) $this->getSummaryCount(),
            'subtotal' => isset($totals['subtotal'])
                ? $this->getQuote()->formatPrice($totals['subtotal'])
                : 0,
            'items' => $this->getRecentItems(),
            'isGuestAllow' => $this->isGuestAllow(),
            'extra_actions' => $this->layout->createBlock('Magento\Catalog\Block\ShortcutButtons')->toHtml(),
            'website_id' => $this->getQuote()->getStore()->getWebsiteId()
        ];
    }

    /**
     * Get active quote
     *
     * @return \Vnecoms\Quotation\Model\Quote
     */
    protected function getQuote()
    {
        if (null === $this->quote) {
            $this->quote = $this->session->getQuote();
        }
        return $this->quote;
    }

    /**
     * Get shopping cart items qty based on configuration (summary qty or items qty)
     *
     * @return int|float
     */
    protected function getSummaryCount()
    {
        if (!$this->summeryCount) {
            $this->summeryCount = $this->session->getQuote()->getItemsQty() ?: 0;
        }
        return $this->summeryCount;
    }

    /**
     * Get array of last added items
     *
     * @return \Vnecoms\Quotation\Model\Item[]
     */
    protected function getRecentItems()
    {
        $items = [];
        if (!$this->getSummaryCount()) {
            return $items;
        }
        foreach (array_reverse($this->getAllQuoteItems()) as $item) {
            /* @var $item \Vnecoms\Quotation\Model\Item */
            if (!$item->getProduct()->isVisibleInSiteVisibility()) {
                $product =  $item->getProduct();
                $products = $this->catalogUrl->getRewriteByProductStore([$product->getId() => $item->getStoreId()]);
                if (isset($products[$product->getId()])) {
					$urlDataObject = new \Magento\Framework\DataObject($products[$product->getId()]);
					$item->getProduct()->setUrlDataObject($urlDataObject);
				}
            }
            $items[] = $this->getItemData($item);
        }
        return $items;
    }

    public function getItemData(Item $item)
    {
        return $this->itemData->getItemData($item);
    }

    /**
     * Return customer quote items
     *
     * @return \Vnecoms\Quotation\Model\Item[]
     */
    protected function getAllQuoteItems()
    {
        return $this->getQuote()->getAllVisibleItems();
    }

    /**
     * Check if guest quote is allowed
     *
     * @return bool
     */
    public function isGuestAllow()
    {
        return (bool)!$this->helper->requireCustomerLogin();
    }
}
