<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\Quotation\Model;

use Vnecoms\Quotation\Helper\Data as HelperData;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Locale\ResolverInterface;

/**
 * @deprecated
 */
class Sidebar
{
    /**
     * @var Session
     */
    protected $session;

    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @var ResolverInterface
     */
    protected $resolver;

    /**
     * @var int
     */
    protected $summaryQty;


    public function __construct(
        Session $session,
        HelperData $helperData,
        ResolverInterface $resolver
    ) {
        $this->session = $session;
        $this->helperData = $helperData;
        $this->resolver = $resolver;
    }

    /**
     * Compile response data
     *
     * @param string $error
     * @return array
     */
    public function getResponseData($error = '')
    {
        if (empty($error)) {
            $response = [
                'success' => true,
            ];
        } else {
            $response = [
                'success' => false,
                'error_message' => $error,
            ];
        }
        return $response;
    }

    /**
     * Check if required quote item exist
     *
     * @param int $itemId
     * @throws LocalizedException
     * @return $this
     */
    public function checkQuoteItem($itemId)
    {
        $item = $this->session->getQuote()->getItemById($itemId);
        if (!$item instanceof \Vnecoms\Quotation\Model\Item) {
            throw new LocalizedException(__('We can\'t find the quote item.'));
        }
        return $this;
    }

    /**
     * Remove quote item
     *
     * @param int $itemId
     * @return $this
     */
    public function removeQuoteItem($itemId)
    {
        $this->session->getQuote()->removeItem($itemId);
        $this->save();
        return $this;
    }

    /**
     * Update quote item
     *
     * @param int $itemId
     * @param int $itemQty
     * @throws LocalizedException
     * @return $this
     */
    public function updateQuoteItem($itemId, $itemQty)
    {
        $itemData = [$itemId => ['qty' => $this->normalize($itemQty)]];

        $infoDataObject = new \Magento\Framework\DataObject($itemData);
        foreach ($itemData as $itemId => $itemInfo) {
            $item = $this->session->getQuote()->getItemById($itemId);
            if (!$item) {
                continue;
            }

            //remove item
            if (!empty($itemInfo['remove']) || isset($itemInfo['qty']) && $itemInfo['qty'] == '0') {
                $this->removeQuoteItem($itemId);
                continue;
            }

            $qty = isset($itemInfo['qty']) ? (double)$itemInfo['qty'] : false;

            if ($qty > 0) {
                $item->getDefaultProposal()->setItem($item)->setQty($qty)->save();
            }
        }
        $this->save();
        return $this;
    }

    /**
     * Apply normalization filter to item qty value
     *
     * @param int $itemQty
     * @return int|array
     */
    protected function normalize($itemQty)
    {

        if ($itemQty) {
            $filter = new \Magento\Framework\Filter\LocalizedToNormalized(
                ['locale' => $this->resolver->getLocale()]
            );
            return $filter->filter((string)$itemQty);
        }
        return $itemQty;
    }

    /**
     * Retrieve summary qty
     *
     * @return int
     */
    protected function getSummaryQty()
    {
        if (!$this->summaryQty) {
            $this->summaryQty = $this->session->getQuote()->getItemsCount()*1;
        }
        return $this->summaryQty;
    }

    /**
     * Retrieve summary qty text
     *
     * @return string
     */
    protected function getSummaryText()
    {
        return ($this->getSummaryQty() == 1) ? __(' item') : __(' items');
    }

    /**
     * Retrieve subtotal block html
     *
     * @return string
     */
    protected function getSubtotalHtml()
    {
        $totals = $this->session->getQuote()->getTotals();
        $subtotal = isset($totals['subtotal'])
            ? $totals['subtotal']
            : 0;
        return $this->helperData->formatPrice($subtotal);
    }

    /**
     * @return $this
     */
    public function save()
    {
        $this->session->getQuote()->collectTotals();
        $this->session->getQuote()->save();
        $this->session->setQuoteId($this->session->getQuote()->getId());

        return $this;
    }
}
