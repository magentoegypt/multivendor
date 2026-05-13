<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

// @codingStandardsIgnoreFile

namespace Vnecoms\RMA\Model\Pdf;

use Magento\Framework\App\Filesystem\DirectoryList;

abstract class AbstractPdf extends \Magento\Sales\Model\Order\Pdf\AbstractPdf
{

    /**
     * Insert requpdf page
     *
     * @param \Zend_Pdf_Page &$page
     * @param \Magento\Sales\Model\Order $obj
     * @param bool $putOrderId
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function insertRequest(&$page, $obj, $putOrderId = true)
    {
        $order = $obj->getOrderObject();

        $this->y = $this->y ? $this->y : 815;
        $top = $this->y;

        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0.45));
        $page->setLineColor(new \Zend_Pdf_Color_GrayScale(0.45));
        $page->drawRectangle(25, $top, 570, $top - 55);
        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(1));
        $this->setDocHeaderCoordinates([25, $top, 570, $top - 55]);
        $this->_setFontRegular($page, 10);

        if ($putOrderId) {
            $page->drawText(__('RMA # ') . $obj->getIncrementId(), 35, $top -= 20, 'UTF-8');
        }

        $top -= 10;
        $page->setFillColor(new \Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
        $page->setLineColor(new \Zend_Pdf_Color_GrayScale(0.5));
        $page->setLineWidth(0.5);
        if($obj->getTrackingCode()) {
            $page->drawRectangle(25, $top, 570, $top - 140);
           // $page->drawRectangle(275, $top, 570, $top - 140);
        }
        else{
            $page->drawRectangle(25, $top, 570, $top - 120);
          //  $page->drawRectangle(275, $top, 570, $top - 100);
        }

        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
        $this->_setFontBold($page, 12);
        $top -= 10;
        //row 1

        $this->_setFontRegular($page, 12);
        $page->drawText(__('Request Type :'), 35, $top - 15, 'UTF-8');
        $this->_setFontBold($page, 12);
        $page->drawText($obj->getTypeTitle(), 135, $top - 15, 'UTF-8');
        $this->_setFontRegular($page, 12);
        $page->drawText(__('Order ID:'), 285, $top - 15, 'UTF-8');
        $this->_setFontBold($page, 12);
        $page->drawText($obj->getOrderIncrementalId(), 345, $top - 15, 'UTF-8');
        $this->_setFontRegular($page, 12);
        //row 3
        $page->drawText(__('Package Opened :'), 35, $top - 40, 'UTF-8');
        $this->_setFontBold($page, 12);
        $page->drawText($obj->getPackageOpenedLabel(), 135, $top - 40, 'UTF-8');
        $this->_setFontRegular($page, 12);
        $page->drawText(__('Status :'), 285, $top - 40, 'UTF-8');
        $this->_setFontBold($page, 12);
        $page->drawText($obj->getStatusTitle(), 345, $top - 40, 'UTF-8');
        $this->_setFontRegular($page, 12);
        //row 2
        $page->drawText(__('Create At :'), 35, $top - 65, 'UTF-8');
        $this->_setFontBold($page, 12);
        $page->drawText($this->getFormatDateHtml($obj->getCreatedAt()), 100, $top - 65, 'UTF-8');
        $this->_setFontRegular($page, 12);

        if($obj->getTrackingCode()){
            $page->drawText(__('Tracking Number:'), 285, $top - 65, 'UTF-8');
            $this->_setFontBold($page, 12);
            $page->drawText($obj->getTrackingCode(), 385, $top - 65, 'UTF-8');
            $this->_setFontRegular($page, 12);
            $page->drawText(__('Reason :'), 35, $top - 90, 'UTF-8');
            $this->_setFontBold($page, 12);
            $text = $obj->getReasonTitle();
            $textChunk = wordwrap($text, 70, "\n");
            $line = $top - 90;
            foreach(explode("\n", $textChunk) as $textLine){
                if ($textLine!=='') {
                    $page->drawText(strip_tags(ltrim($textLine)), 100, $line , 'UTF-8');
                    $line -=12;
                }
            }
            $this->_setFontRegular($page, 12);

        }else{
            $page->drawText(__('Reason :'), 285, $top - 65, 'UTF-8');
            $this->_setFontBold($page, 12);
            $text = $obj->getReasonTitle();
            $textChunk = wordwrap($text, 40, "\n");
            $line = $top - 65;
            foreach(explode("\n", $textChunk) as $textLine){
                if ($textLine!=='') {
                    $page->drawText(strip_tags(ltrim($textLine)), 345, $line , 'UTF-8');
                    $line -=12;
                }
            }
        }
        if($obj->getTrackingCode()) {
            $this->y = $top - 150;
        }else{
            $this->y = $top - 130;
        }
    }

    /**
     * format date html
     * @param $date
     * @return mixed
     */
    public function getFormatDateHtml($date) {
        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $dateObj = $object_manager->get('\Magento\Framework\Stdlib\DateTime\DateTime');
        return $dateObj->date('F j, Y, g:i a',$date);
    }

    /**
     * Draw Item process
     *
     * @param  \Magento\Framework\DataObject $item
     * @param  \Zend_Pdf_Page $page
     * @param  \Magento\Sales\Model\Order $order
     * @return \Zend_Pdf_Page
     */
    protected function _drawItem(\Magento\Framework\DataObject $item, \Zend_Pdf_Page $page, \Magento\Sales\Model\Order $order)
    {
        $type = $item->getProductType();
        $renderer = $this->_getRenderer($type);
        $renderer->setOrder($order);
        $renderer->setItem($item);
        $renderer->setPdfRma($this);
        $renderer->setPage($page);
        $renderer->setRenderedModel($this);

        $renderer->draw();

        return $renderer->getPage();
    }

}
