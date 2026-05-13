<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Model\Pdf;

use Vnecoms\RMA\Model\ResourceModel\Request\Collection;

/**
 *  PDF model
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Request extends \Vnecoms\RMA\Model\Pdf\AbstractPdf
{
    
    /**
     * Draw header for item table
     *
     * @param \Zend_Pdf_Page $page
     * @return void
     */
    protected function _drawHeader(\Zend_Pdf_Page $page)
    {
        /* Add table head */
        $this->_setFontRegular($page, 12);
        $page->setFillColor(new \Zend_Pdf_Color_RGB(0.93, 0.92, 0.92));
        $page->setLineColor(new \Zend_Pdf_Color_GrayScale(0.5));
        $page->setLineWidth(0.5);
        $page->drawRectangle(25, $this->y, 570, $this->y - 25);
        $this->y -= 15;
        $page->setFillColor(new \Zend_Pdf_Color_RGB(0, 0, 0));

        //columns headers
        $lines[0][] = ['text' => __('Products'), 'feed' => 35];

        $lines[0][] = ['text' => __('Qty'), 'feed' => 510, 'align' => 'right'];

        $lineBlock = ['lines' => $lines, 'height' => 5];

        $this->drawLineBlocks($page, [$lineBlock], ['table_header' => true]);
        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
        $this->y -= 30;
    }

    /**
     * Return PDF document
     *
     * @param array|Collection $request
     * @return \Zend_Pdf
     */
    public function getPdf($requests = [])
    {
        $this->_beforeGetPdf();
        $this->_initRenderer('rma');

        $pdf = new \Zend_Pdf();
        $this->_setPdf($pdf);
        $style = new \Zend_Pdf_Style();
        $this->_setFontBold($style, 10);


        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $localeResolver = $object_manager->get('\Magento\Framework\Locale\ResolverInterface');
        $storeManager = $object_manager->get('\Magento\Store\Model\StoreManagerInterface');


        foreach ($requests as $request) {
            $request = $request->load($request->getId());
            $storeId = $request->getOrderObject()->getStoreId();
            if ($storeId) {
                $localeResolver->emulate($storeId);
                $storeManager->setCurrentStore($storeId);
            }

            $page = $this->newPage();
            $order = $request->getOrderObject();
            /* Add image */
            $this->insertLogo($page, $storeId);
            /* Add head */
            $this->insertRequest(
                $page,
                $request,
                true
            );

            $this->_drawHeader($page);

            foreach ($request->getAllItemFromRequest() as $itemRma) {
                $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
                $item = $object_manager->get('\Magento\Sales\Model\Order\Item')
                    ->load($itemRma->getOrderItemId());
                $item->setQty($itemRma->getQty());
                if ($item->getParentItem()) {
                    continue;
                }
                $this->_drawItem($item, $page, $order);
                $page = end($pdf->pages);
            }

            if ($storeId) {
                $localeResolver->revert();
            }
        }
        $this->_afterGetPdf();
        return $pdf;
    }

    /**
     * Create new page and assign to PDF object
     *
     * @param  array $settings
     * @return \Zend_Pdf_Page
     */
    public function newPage(array $settings = [])
    {
        /* Add new table head */
        $page = $this->_getPdf()->newPage(\Zend_Pdf_Page::SIZE_A4);
        $this->_getPdf()->pages[] = $page;
        $this->y = 800;
        if (!empty($settings['table_header'])) {
            $this->_drawHeader($page);
        }
        return $page;
    }
}
