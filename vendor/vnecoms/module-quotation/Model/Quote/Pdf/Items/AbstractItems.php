<?php
/**
 * Copyright © 2018 Vnecoms. All rights reserved.
 * See LICENSE.txt for license details.
 */


namespace Vnecoms\Quotation\Model\Quote\Pdf\Items;

use Vnecoms\Quotation\Model\Quote;

/**
 * Class with class map capability
 *
 * ...
 */
abstract class AbstractItems extends \Magento\Framework\Model\AbstractModel
{

    /**
     * quote model
     *
     * @var \Vnecoms\Quotation\Model\Quote
     */
    protected $_quote;

    /**
     * Item object
     *
     * @var \Magento\Framework\DataObject
     */
    protected $_item;

    /**
     * Pdf object
     *
     * @var \Magento\Sales\Model\Order\Pdf\AbstractPdf
     */
    protected $_pdf;

    /**
     * Pdf current page
     *
     * @var \Zend_Pdf_Page
     */
    protected $_pdfPage;

    public function setQuote(Quote $quote)
    {
        $this->_quote = $quote;
        return $this;
    }

    /**
     * Retrieve Pdf quote object
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return \Vnecoms\Quotation\Model\Quote
     */
    public function getQuote()
    {
        if (null === $this->_quote) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The quote object is not specified.'));
        }

        return $this->_quote;
    }

    /**
     * Retrieve item object
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return \Magento\Framework\DataObject
     */
    public function getItem()
    {
        if (null === $this->_item) {
            throw new \Magento\Framework\Exception\LocalizedException(__('An item object is not specified.'));
        }
        return $this->_item;
    }

    /**
     * Retrieve Pdf model
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return \Magento\Sales\Model\Order\Pdf\AbstractPdf
     */
    public function getPdf()
    {
        if (null === $this->_pdf) {
            throw new \Magento\Framework\Exception\LocalizedException(__('A PDF object is not specified.'));
        }
        return $this->_pdf;
    }

    /**
     * Retrieve Pdf page object
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return \Zend_Pdf_Page
     */
    public function getPage()
    {
        if (null === $this->_pdfPage) {
            throw new \Magento\Framework\Exception\LocalizedException(__('A PDF page object is not specified.'));
        }
        return $this->_pdfPage;
    }

    /**
     * Set item object
     *
     * @param  \Magento\Framework\DataObject $item
     * @return $this
     */
    public function setItem(\Magento\Framework\DataObject $item)
    {
        $this->_item = $item;
        return $this;
    }

    /**
     * Set Pdf model
     *
     * @param  \Magento\Sales\Model\Order\Pdf\AbstractPdf $pdf
     * @return $this
     */
    public function setPdf(\Magento\Sales\Model\Order\Pdf\AbstractPdf $pdf)
    {
        $this->_pdf = $pdf;
        return $this;
    }

    /**
     * Set current page
     *
     * @param  \Zend_Pdf_Page $page
     * @return $this
     */
    public function setPage(\Zend_Pdf_Page $page)
    {
        $this->_pdfPage = $page;
        return $this;
    }

    /**
     * Draw item line
     *
     * @return void
     */
    abstract public function draw();


    /**
     * Set font as regular
     *
     * @param  int $size
     * @return \Zend_Pdf_Resource_Font
     */
    protected function _setFontRegular($size = 7)
    {
        $font = \Zend_Pdf_Font::fontWithPath(
            $this->_rootDirectory->getAbsolutePath('lib/internal/LinLibertineFont/LinLibertine_Re-4.4.1.ttf')
        );
        $this->getPage()->setFont($font, $size);
        return $font;
    }

    /**
     * Set font as bold
     *
     * @param  int $size
     * @return \Zend_Pdf_Resource_Font
     */
    protected function _setFontBold($size = 7)
    {
        $font = \Zend_Pdf_Font::fontWithPath(
            $this->_rootDirectory->getAbsolutePath('lib/internal/LinLibertineFont/LinLibertine_Bd-2.8.1.ttf')
        );
        $this->getPage()->setFont($font, $size);
        return $font;
    }

    /**
     * Set font as italic
     *
     * @param  int $size
     * @return \Zend_Pdf_Resource_Font
     */
    protected function _setFontItalic($size = 7)
    {
        $font = \Zend_Pdf_Font::fontWithPath(
            $this->_rootDirectory->getAbsolutePath('lib/internal/LinLibertineFont/LinLibertine_It-2.8.2.ttf')
        );
        $this->getPage()->setFont($font, $size);
        return $font;
    }

    /**
     * Return item Sku
     *
     * @param mixed $item
     * @return mixed
     */
    public function getSku($item)
    {
        if ($item->getQuote()->getProductOptionByCode('simple_sku')) {
            return $item->getQuote()->getProductOptionByCode('simple_sku');
        } else {
            return $item->getSku();
        }
    }
}
