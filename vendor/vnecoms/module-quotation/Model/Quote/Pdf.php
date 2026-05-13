<?php
/**
 * Copyright © 2018 Vnecoms. All rights reserved.
 * See LICENSE.txt for license details.
 */


namespace Vnecoms\Quotation\Model\Quote;

use Magento\Sales\Model\Order\Pdf\Config;
use Vnecoms\Quotation\Model\Quote;
use Magento\Framework\Module\Dir;

class Pdf extends \Magento\Sales\Model\Order\Pdf\Invoice
{
    protected $quotes;

    /**
     * @var \Magento\Framework\Module\Dir\Reader
     */
    protected $moduleReader;

    /**
     * Pdf constructor.
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Filesystem $filesystem
     * @param Config $pdfConfig
     * @param \Magento\Sales\Model\Order\Pdf\Total\Factory $pdfTotalFactory
     * @param \Magento\Sales\Model\Order\Pdf\ItemsFactory $pdfItemsFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param \Magento\Sales\Model\Order\Address\Renderer $addressRenderer
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Store\Model\App\Emulation $appEmulation
     * @param Dir\Reader $moduleReader
     * @param array $data
     */
    public function __construct(
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Filesystem $filesystem,
        Config $pdfConfig,
        \Magento\Sales\Model\Order\Pdf\Total\Factory $pdfTotalFactory,
        \Magento\Sales\Model\Order\Pdf\ItemsFactory $pdfItemsFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Sales\Model\Order\Address\Renderer $addressRenderer,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Store\Model\App\Emulation $appEmulation,
        \Magento\Framework\Module\Dir\Reader $moduleReader,
        array $data = []
    ) {
        $this->moduleReader = $moduleReader;
        parent::__construct(
            $paymentData,
            $string,
            $scopeConfig,
            $filesystem,
            $pdfConfig,
            $pdfTotalFactory,
            $pdfItemsFactory,
            $localeDate,
            $inlineTranslation,
            $addressRenderer,
            $storeManager,
            $appEmulation,
            $data
        );
    }

    /**
     * Draw header pdf
     *
     * @param \Zend_Pdf_Page $page
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _drawHeader(\Zend_Pdf_Page $page)
    {
        /* Add table head */
        $this->_setFontRegular($page, 10);
        $page->setFillColor(new \Zend_Pdf_Color_Rgb(0.93000000000000005, 0.92000000000000004, 0.92000000000000004));
        $page->setLineColor(new \Zend_Pdf_Color_GrayScale(0.5));
        $page->setLineWidth(0.5);
        $page->drawRectangle(25, $this->y, 570, $this->y - 15);
        $this->y -= 10;
        $page->setFillColor(new \Zend_Pdf_Color_Rgb(0, 0, 0));

        //columns headers
        $lines[0][] = array('text' => __('Products'), 'feed' => 35);
        $lines[0][] = array('text' => __('SKU'), 'feed' => 250, 'align' => 'right');
        $lines[0][] = array('text' => __('Origin Price'), 'feed' => 359, 'align' => 'right', 'width' => 70);
        $lines[0][] = array('text' => __('Qty'), 'feed' => 408, 'align' => 'right', 'width' => 75);
        $lines[0][] = array('text' => __('Proposals'), 'feed' => 560, 'align' => 'right');

        $lineBlock = ['lines' => $lines, 'height' => 5];

        $this->drawLineBlocks($page, [$lineBlock], ['table_header' => true]);
        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
        $this->y -= 20;
    }

    /**
     * Retrieve pdf obj
     *
     * @param array $quotes
     * @return \Zend_Pdf
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Pdf_Exception
     */
    public function getPdf($quotes = [])
    {
        $this->_beforeGetPdf();
        $this->_initRenderer('quotation');

        $pdf = new \Zend_Pdf();
        $this->_setPdf($pdf);
        $style = new \Zend_Pdf_Style();
        $this->_setFontBold($style, 10);

        foreach ($quotes as $quote) {
            if ($quote->getStoreId()) {
                $this->_localeResolver->emulate($quote->getStoreId());
                $this->_storeManager->setCurrentStore($quote->getStoreId());
            }
            $store = $quote->getStore();
            $page = $this->newPage();
            /* Add image */
            $this->insertLogo($page, $store);

            /* Add address */
            $this->insertAddress($page, $store);
            /* Add quote to head */
            $this->insertQuote($page, $quote);

            /* Add document text and number */
            //$this->insertDocumentNumber($page, __('Quote # ') . $quote->getId());
            /* Add table */
            $this->_drawHeader($page);
            /* Add body */
            foreach ($quote->getAllItems() as $item) {
                if ($item->getParentItem() && ($item->getParentItem()->getProductType() != 'bundle')) {
                    continue;
                }
                /* Draw quote item */
                $this->_drawQuoteItem($item, $page, $quote);
                $page = end($pdf->pages);
            }

            /* Add totals */
            //$totalsY = $this->y;
            //$this->insertTotals($page, $quote);
            //$this->y = $totalsY;

            /* Add totals */
            if ($quote->getStoreId()) {
                $this->_localeResolver->revert();
            }
        }
        $this->_afterGetPdf();
        return $pdf;
    }

    /**
     * {@inheritdoc}
     */
    protected function insertLogo(&$page, $store = null)
    {
        $this->y = $this->y ? $this->y : 815;
        $image = $this->_scopeConfig->getValue(
            'sales/identity/logo',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
        if ($image) {
            $imagePath = '/sales/store/logo/' . $image;
            if ($this->_mediaDirectory->isFile($imagePath)) {
                $image = \Zend_Pdf_Image::imageWithPath($this->_mediaDirectory->getAbsolutePath($imagePath));
            }

        } else {
            // If logo was not defined use module image
            $quotationViewPath = $this->moduleReader->getModuleDir(Dir::MODULE_VIEW_DIR, 'Vnecoms_Quotation');
            $imagePath = $quotationViewPath. '/base/web/images/pdf-logo-thumb.png';
            $image = \Zend_Pdf_Image::imageWithPath($imagePath);
        }

        $top = 830;
        //top border of the page
        $widthLimit = 270;
        //half of the page width
        $heightLimit = 270;
        //assuming the image is not a "skyscraper"
        $width = $image->getPixelWidth();
        $height = $image->getPixelHeight();

        //preserving aspect ratio (proportions)
        $ratio = $width / $height;
        if ($ratio > 1 && $width > $widthLimit) {
            $width = $widthLimit;
            $height = $width / $ratio;
        } elseif ($ratio < 1 && $height > $heightLimit) {
            $height = $heightLimit;
            $width = $height * $ratio;
        } elseif ($ratio == 1 && $height > $heightLimit) {
            $height = $heightLimit;
            $width = $widthLimit;
        }

        $y1 = $top - $height;
        $y2 = $top;
        $x1 = 25;
        $x2 = $x1 + $width;

        //coordinates after transformation are rounded by Zend
        $page->drawImage($image, $x1, $y1, $x2, $y2);
        $this->y = $y1 - 10;
    }

    /**
     * {@inheritdoc}
     */
    protected function insertAddress(&$page, $store = null)
    {
        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
        $font = $this->_setFontRegular($page, 10);
        $page->setLineWidth(0);
        $this->y = $this->y ? $this->y : 815;
        $top = 815;
        foreach (explode(
                     "\n",
                     $this->_scopeConfig->getValue(
                         'sales/identity/address',
                         \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                         $store
                     )
                 ) as $value) {
            if ($value !== '') {
                $value = preg_replace('/<br[^>]*>/i', "\n", $value);
                foreach ($this->string->split($value, 45, true, true) as $_value) {
                    $page->drawText(
                        trim(strip_tags($_value)),
                        $this->getAlignRight($_value, 130, 440, $font, 10),
                        $top,
                        'UTF-8'
                    );
                    $top -= 10;
                }
            }
        }
        $this->y = $this->y > $top ? $top : $this->y;
    }

    /**
     * Insert quote to pdf
     *
     * @param \Zend_Pdf_Page $page
     * @param \Vnecoms\Quotation\Model\Quote $obj
     * @param bool $putQuoteId
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @throws
     */
    protected function insertQuote(&$page, $obj, $putQuoteId = true)
    {
        if ($obj instanceof \Vnecoms\Quotation\Model\Quote) {
            $shipment = null;
            $quote = $obj;
        } elseif ($obj instanceof \Magento\Sales\Model\Order\Shipment) {
            $shipment = $obj;
            $quote = $shipment->getQuote();
        }

        $this->y = $this->y ? $this->y : 815;
        $top = $this->y;
        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0.45000000000000001));
        $page->setLineColor(new \Zend_Pdf_Color_GrayScale(0.45000000000000001));
        $page->drawRectangle(25, $top, 570, $top - 75);
        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(1));
        $this->setDocHeaderCoordinates(array(25, $top, 570, $top - 75));
        $this->_setFontRegular($page, 10);

        if ($putQuoteId) {
            $page->drawText(__('Quotation # ') . $quote->getIncrementId(), 35, $top -= 30, 'UTF-8');
        }

        $page->drawText(
            __('Quotation Created Date: ') .
            $this->_localeDate->formatDate(
                $this->_localeDate->scopeDate(
                    $quote->getStore(),
                    $quote->getCreatedAt(),
                    false
                ),
                \IntlDateFormatter::MEDIUM,
                false
            ),
            35,
            $top -= 15,
            'UTF-8'
        );

        if ($quote->getExpiredDate()) {
            $page->drawText(
                __('Quotation Valid Until: ') .
                $this->_localeDate->formatDate(
                    $this->_localeDate->scopeDate(
                        $quote->getStore(),
                        $quote->getExpiredDate(),
                        false
                    ),
                    \IntlDateFormatter::MEDIUM,
                    false
                ),
                35,
                $top -= 15,
                'UTF-8'
            );
        }

        if ($quote->getCustomerIsGuest()) {
            $this->y = $top - 40;
            return NULL;
        }

        $top -= 10;
        $page->setFillColor(new \Zend_Pdf_Color_Rgb(0.93000000000000005, 0.92000000000000004, 0.92000000000000004));
        $page->setLineColor(new \Zend_Pdf_Color_GrayScale(0.5));
        $page->setLineWidth(0.5);
        $page->drawRectangle(25, $top, 275, $top - 25);
        $page->drawRectangle(275, $top, 570, $top - 25);

        /* Calculate blocks info */

        /* Quote Information */
        $quoteData = [
            'note' => $quote->getNote(),
            'shipping_description' => $quote->getShippingDescription(),
            'customer_comment' => $quote->getClientComment()
        ];

        /* Customer Information */
        $customerData = [
            'customer_name' => $quote->getCustomerName(),
            'customer_email' => $quote->getCustomerEmail(),
            'company' => $quote->getCustomerCompany(),
            'phone' => $quote->getCustomerPhone(),
            'tax_vat' => $quote->getCustomerTaxvat()
        ];


        //$page->setFillColor(new \Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
        //$page->setLineColor(new \Zend_Pdf_Color_GrayScale(0.5));
        //$page->setLineWidth(0.5);
        //$page->drawRectangle(25, $top, 275, $top - 25);
        //$page->drawRectangle(275, $top, 570, $top - 25);

        /* Billing Address */
        //$billingAddress = $this->_formatAddress($this->addressRenderer->format($quote->getBillingAddress(), 'pdf'));

        /* Payment */
        //$paymentInfo = $this->_paymentData->getInfoBlock($quote->getPayment())->setIsSecureMode(true)->toPdf();
        //$paymentInfo = htmlspecialchars_decode($paymentInfo, ENT_QUOTES);
        //$payment = explode('{{pdf_row_separator}}', $paymentInfo);
//        foreach ($payment as $key => $value) {
//            if (strip_tags(trim($value)) == '') {
//                unset($payment[$key]);
//            }
//        }
//        reset($payment);

        /* Shipping Address and Method */
//        if (!$quote->getIsVirtual()) {
//            $shippingAddress = $this->_formatAddress($this->addressRenderer->format($quote->getShippingAddress(), 'pdf'));
//            $shippingMethod = $quote->getShippingDescription();
//        }

        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
        $this->_setFontBold($page, 12);
        $page->drawText(__('Quote Information:'), 35, $top - 15, 'UTF-8');

        if (!$quote->getIsVirtual()) {
            $page->drawText(__('Customer Information:'), 285, $top - 15, 'UTF-8');
        } else {
            $page->drawText(__('Customer Information:'), 285, $top - 15, 'UTF-8');
        }

        $quoteDataHeight = $this->_calcAddressHeight($quoteData);
        if (isset($customerData)) {
            $quoteDataHeight = max($quoteDataHeight, $this->_calcAddressHeight($customerData));
        }

        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(1));
        $page->drawRectangle(25, $top - 25, 570, $top - 33 - $quoteDataHeight);
        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
        $this->_setFontRegular($page, 10);
        $this->y = $top - 35;
        $quoteStartY = $this->y;

        foreach ($quoteData as $value) {
            if ($value !== '') {
                $text = [];
                foreach ($this->string->split($value, 45, true, true) as $_value) {
                    $text[] = $_value;
                }
                foreach ($text as $part) {
                    $page->drawText(strip_tags(ltrim($part)), 35, $this->y, 'UTF-8');
                    $this->y -= 15;
                }
            }
        }

        $quoteEndY = $this->y;

        if (!$quote->getIsVirtual()) {
            $this->y = $quoteStartY;
            foreach ($customerData as $value) {
                if ($value !== '') {
                    $text = [];
                    foreach ($this->string->split($value, 45, true, true) as $_value) {
                        $text[] = $_value;
                    }
                    foreach ($text as $part) {
                        $page->drawText(strip_tags(ltrim($part)), 285, $this->y, 'UTF-8');
                        $this->y -= 15;
                    }
                }
            }

            $quoteEndY = min($quoteEndY, $this->y);
            $this->y = $quoteEndY;

            //$page->setFillColor(new \Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
            //$page->setLineWidth(0.5);
            //$page->drawRectangle(25, $this->y, 275, $this->y - 25);
            //$page->drawRectangle(275, $this->y, 570, $this->y - 25);

            //$this->y -= 15;
            //$this->_setFontBold($page, 12);
            //$page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
            //$page->drawText(__('Customer Info'), 35, $this->y, 'UTF-8');
            //$page->drawText(__('Payment Method'), 35, $this->y, 'UTF-8');
            //$page->drawText(__('Shipping Method:'), 285, $this->y, 'UTF-8');

            //$this->y -= 10;
            $page->setFillColor(new \Zend_Pdf_Color_GrayScale(1));

            $this->_setFontRegular($page, 10);
            $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));

            $paymentLeft = 35;
            $yPayments = $this->y - 15;
        } else {
            $yPayments = $quoteStartY;
            $paymentLeft = 285;
        }

//        foreach ($customerData as $value) {
//            if (trim($value) != '') {
//                //Printing "Customer Information" lines
//                $value = preg_replace('/<br[^>]*>/i', "\n", $value);
//                foreach ($this->string->split($value, 45, true, true) as $_value) {
//                    $page->drawText(strip_tags(trim($_value)), $paymentLeft, $yPayments, 'UTF-8');
//                    $yPayments -= 15;
//                }
//            }
//        }

        if ($quote->getIsVirtual()) {
            // replacement of Shipments-Payments rectangle block
            $yPayments = min($quoteEndY, $yPayments);
//            $page->drawLine(25, $top - 25, 25, $yPayments);
//            $page->drawLine(570, $top - 25, 570, $yPayments);
//            $page->drawLine(25, $yPayments, 570, $yPayments);

            $this->y = $yPayments - 15;
        } else {
            //$topMargin = 15;
            //$methodStartY = $this->y;
            $this->y -= 15;

            //$yShipments = $this->y;

//            foreach ($this->string->split($shippingMethod, 45, true, true) as $_value) {
//                $page->drawText(strip_tags(trim($_value)), 285, $this->y, 'UTF-8');
//                $this->y -= 15;
//            }

//            $yShipments = $this->y;
//            $totalShippingChargesText = "(" . __(
//                    'Total Shipping Charges'
//                ) . " " . $order->formatPriceTxt(
//                    $order->getShippingAmount()
//                ) . ")";
//
//            $page->drawText($totalShippingChargesText, 285, $yShipments - $topMargin, 'UTF-8');
//            $yShipments -= $topMargin + 10;


//            if (count($tracks)) {
//                $page->setFillColor(new \Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
//                $page->setLineWidth(0.5);
//                $page->drawRectangle(285, $yShipments, 510, $yShipments - 10);
//                $page->drawLine(400, $yShipments, 400, $yShipments - 10);
//                //$page->drawLine(510, $yShipments, 510, $yShipments - 10);
//
//                $this->_setFontRegular($page, 9);
//                $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
//                //$page->drawText(__('Carrier'), 290, $yShipments - 7 , 'UTF-8');
//                $page->drawText(__('Title'), 290, $yShipments - 7, 'UTF-8');
//                $page->drawText(__('Number'), 410, $yShipments - 7, 'UTF-8');
//
//                //$yShipments -= 20;
//                //$this->_setFontRegular($page, 8);
//
//            } else {
//                $yShipments -= $topMargin - 5;
//            }

            //$currentY = min($yPayments, $yShipments);

            // replacement of Shipments-Payments rectangle block
//            $page->drawLine(25, $methodStartY, 25, $currentY);
            //left
//            $page->drawLine(25, $currentY, 570, $currentY);
            //bottom
//            $page->drawLine(570, $currentY, 570, $methodStartY);
            //right

            //$this->y = $currentY;
            //$this->y -= 15;
        }


    }

    /**
     * Draw Quote Item process
     *
     * @param  \Vnecoms\Quotation\Model\Item $item
     * @param  \Zend_Pdf_Page $page
     * @param  Quote $quote
     * @return \Zend_Pdf_Page
     * @throws
     */
    protected function _drawQuoteItem(\Vnecoms\Quotation\Model\Item $item, \Zend_Pdf_Page $page, Quote $quote)
    {
        //$type = $item->getProductType();
        $renderer = $this->getRenderer('quoteItem');
        $renderer->setQuote($quote);
        $renderer->setItem($item);
        $renderer->setPdf($this);
        $renderer->setPage($page);
        $renderer->setRenderedModel($this);

        $renderer->draw();

        return $renderer->getPage();
    }

    private function getIncrementId($quotes = [])
    {
        $incrementIds = [];

        foreach ($quotes as $quote) {
            $incrementIds[] = $quote->getIncrementId();
        }

        return __('-', $incrementIds);
    }

    /**
     * @param \Zend_Pdf_Page $page
     * @param array $draw
     * @param array $pageSettings
     * @return \Zend_Pdf_Page
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Pdf_Exception
     */
    public function drawLineBlocks(\Zend_Pdf_Page $page, array $draw, array $pageSettings = [])
    {
        foreach ($draw as $itemsProp) {
            if (!isset($itemsProp['lines']) || !is_array($itemsProp['lines'])) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('We don\'t recognize the draw line data. Please define the "lines" array.')
                );
            }
            $lines = $itemsProp['lines'];
            $height = isset($itemsProp['height']) ? $itemsProp['height'] : 10;

            if (empty($itemsProp['shift'])) {
                $shift = 0;
                foreach ($lines as $line) {
                    $maxHeight = 0;
                    foreach ($line as $column) {
                        $lineSpacing = !empty($column['height']) ? $column['height'] : $height;
                        if (!is_array($column['text'])) {
                            $column['text'] = [$column['text']];
                        }
                        $top = 0;
                        foreach ($column['text'] as $part) {
                            $top += $lineSpacing;
                        }

                        $maxHeight = $top > $maxHeight ? $top : $maxHeight;
                    }
                    $shift += $maxHeight;
                }
                $itemsProp['shift'] = $shift;
            }

            if ($this->y - $itemsProp['shift'] < 15) {
                $page = $this->newPage($pageSettings);
            }

            foreach ($lines as $line) {
                $maxHeight = 0;
                foreach ($line as $column) {
                    $fontSize = empty($column['font_size']) ? 10 : $column['font_size'];
                    if (!empty($column['font_file'])) {
                        $font = \Zend_Pdf_Font::fontWithPath($column['font_file']);
                        $page->setFont($font, $fontSize);
                    } else {
                        $fontStyle = empty($column['font']) ? 'regular' : $column['font'];
                        switch ($fontStyle) {
                            case 'bold':
                                $font = $this->_setFontBold($page, $fontSize);
                                break;
                            case 'italic':
                                $font = $this->_setFontItalic($page, $fontSize);
                                break;
                            default:
                                $font = $this->_setFontRegular($page, $fontSize);
                                break;
                        }
                    }

                    if (!is_array($column['text'])) {
                        $column['text'] = [$column['text']];
                    }

                    $lineSpacing = !empty($column['height']) ? $column['height'] : $height;
                    $top = 0;

                    if (isset($column['isProductLine'])) {
                        $top += 10;
                    }

                    if (isset($column['addToTop'])) {
                        $top += $column['addToTop'];
                    }

                    foreach ($column['text'] as $part) {
                        if ($this->y - $lineSpacing < 15) {
                            $page = $this->newPage($pageSettings);
                        }

                        $feed = $column['feed'];
                        $textAlign = empty($column['align']) ? 'left' : $column['align'];
                        $width = empty($column['width']) ? 0 : $column['width'];
                        switch ($textAlign) {
                            case 'right':
                                if ($width) {
                                    $feed = $this->getAlignRight($part, $feed, $width, $font, $fontSize);
                                } else {
                                    $feed = $feed - $this->widthForStringUsingFontSize($part, $font, $fontSize);
                                }
                                break;
                            case 'center':
                                if ($width) {
                                    $feed = $this->getAlignCenter($part, $feed, $width, $font, $fontSize);
                                }
                                break;
                            default:
                                break;
                        }
                        $page->drawText($part, $feed, $this->y - $top, 'UTF-8');
                        $top += $lineSpacing;
                    }

                    $maxHeight = $top > $maxHeight ? $top : $maxHeight;
                }
                $this->y -= $maxHeight;
            }
        }

        return $page;
    }

    /**
     * @return mixed
     */
    public function getQuotes()
    {
        return $this->quotes;
    }

    /**
     * @param array $quotes
     * @return $this
     */
    public function setQuote($quotes = [])
    {
        foreach ($quotes as $quote) {
            if (!$quote instanceof \Vnecoms\Quotation\Model\Quote) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Invalid quote class provided for the PDF. ' . 'Expected class \Vnecoms\Quotation\Model\Quote'));
            }
        }

        $this->quotes = $quotes;
        return $this;
    }
}
