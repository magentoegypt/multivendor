<?php
namespace MagentoEgypt\VendorExtend\Block\Adminhtml\Quote\Edit;

class Header extends \Vnecoms\Quotation\Block\Adminhtml\Quote\Edit\Header
{
    /**
     * Generate title for new quote creation page.
     *
     * @return string
     */
    protected function _getCreateQuoteTitle()
    {
        if ($this->isQuoteEditPage()) {
            $quoteId = $this->getRealQuote()->getIncrementId();
            $createdAtFormatted = new \DateTime($this->getRealQuote()->getCreatedAt());
            return __(
                'Quote # %1 | %2',
                $quoteId,
                $createdAtFormatted->format('M j, Y g:i:s a')
            );
        }
        return parent::_getCreateQuoteTitle();
    }
}
