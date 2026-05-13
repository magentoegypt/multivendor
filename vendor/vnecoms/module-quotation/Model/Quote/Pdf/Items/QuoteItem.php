<?php
/**
 * Copyright © 2018 Vnecoms. All rights reserved.
 * See LICENSE.txt for license details.
 */


namespace Vnecoms\Quotation\Model\Quote\Pdf\Items;

use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Class with class map capability
 *
 * ...
 */
class QuoteItem extends AbstractItems
{

    /**
     * Core string
     *
     * @var \Magento\Framework\Stdlib\StringUtils
     */
    protected $string;

    protected $filterManager;


    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Tax\Helper\Data $taxData,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Filter\FilterManager $filterManager,
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->filterManager = $filterManager;
        $this->_taxData = $taxData;
        $this->_rootDirectory = $filesystem->getDirectoryRead(DirectoryList::ROOT);
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->string = $string;
        $this->filterManager = $filterManager;
    }

    /**
     * Draw quote item
     *
     * @param null
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function draw()
    {

        /** @var \Vnecoms\Quotation\Model\Quote $quote */
        $quote = $this->getQuote();
        /** @var \Vnecoms\Quotation\Model\Item $item */
        $item = $this->getItem();
        $pdf = $this->getPdf();
        $page = $this->getPage();
        $this->_setFontRegular();
        $prevOptionId = '';
        $drawItems = array();
        $line = array();
        $attributes = $this->getSelectionAttributes($item);

        if (isset($attributes)) {
            $optionId = $attributes['option_id'];
        } else {
            $optionId = 0;
        }

        if (!isset($drawItems[$optionId])) {
            $drawItems[$optionId] = array(
                'lines'  => array(),
                'height' => 15
            );
        }

        if ($item->getParentItem()) {
            if ($prevOptionId != $attributes['option_id']) {
                $line[0] = array('font' => 'italic', 'text' => $this->string->split($attributes['option_label'], 45, true, true), 'feed' => 35);
                $drawItems[$optionId] = array(
                    'lines'  => array($line),
                    'height' => 15
                );
                $line = array();
            }
        }

        if ($item->getParentItem()) {
            $feed = 45;
            $attributes = json_decode($item->getOptionByCode('bundle_selection_attributes')->getValue(), true);
            $name = sprintf('%s x %s', $item->getQty(), $item->getName());

            if (!isset($name)) {
                $name = $attributes['option_label'];
            }
        }
        else {
            $feed = 35;
            $name = $item->getName();
            $nameArray['font'] = 'bold';
        }

        $nameArray['text'] = $this->string->split($name, 35, true, true);
        $nameArray['feed'] = $feed;
        $nameArray['addToTop'] = -5;
        $nameArray['isProductLine'] = true;
        $line[] = $nameArray;
        //$nameLineCount = count($nameArray['text']);

        $splitDescription = array();

        $comment = '';

        $text = array();

        foreach ($this->string->split($item->getSku(), 27) as $part) {
            $text[] = $part;
        }

        $line[] = array('text' => $text, 'feed' => 230, 'isProductLine' => true, 'addToTop' => -5);
        $fontType = 'bold';
        $qty = $item->getQty();

        if ($item->getParentItem()) {
            $fontType = 'regular';
            //$tax = null;
            //$row_total = null;
            $qty = null;
        } else {
           // $tax = $quote->formatPriceTxt($item->getTaxAmount());
            //$row_total = $quote->formatPriceTxt($item->getRowTotal());
        }


        $originPrice = $quote->formatPriceTxt($item->getOriginPrice());
        $line[] = array('text' => $originPrice, 'feed' => 415, 'font' => $fontType, 'align' => 'right', 'isProductLine' => true, 'addToTop' => -5);
        $firstLine = false;
        
        foreach($item->getProposalsCollection() as $proposal){
            $line[] = array('text' => $proposal->getQty() * 1, 'feed' => 468, 'font' => $fontType, 'isProductLine' => true, 'addToTop' => -5, 'width' => 50);
            $line[] = array('text' => $quote->formatPriceTxt($proposal->getPrice()), 'feed' => 560, 'font' => $fontType, 'align' => 'right', 'isProductLine' => true, 'addToTop' => -5);
            if(!$firstLine){
                $firstLine = true;
                $drawItems[$optionId]['lines'][] = $line;
                $line = [];
            }else{
                $drawItems[] = ['lines' => [$line], 'height' => 15];
            }
        }

        $options = $item->getProductOptions();

        if ($options) {
            if (isset($options['options'])) {
                foreach ($options['options'] as $option) {
                    $lines = array();
                    $lines[][] = array('text' => $this->string->split($this->filterManager->stripTags($option['label']), 40, true, true), 'font' => 'italic', 'feed' => 35);

                    if ($option['value']) {
                        $text = array();
                        $printValue = (isset($option['print_value']) ? $option['print_value'] : $this->filterManager->stripTags($option['value']));
                        $values = explode(', ', $printValue);

                        foreach ($values as $value) {
                            foreach ($this->string->split($value, 30, true, true) as $subValue) {
                                $text[] = $subValue;
                            }
                        }

                        $lines[][] = array('text' => $text, 'feed' => 40);
                    }

                    $drawItems[] = array('lines' => $lines, 'height' => 15);
                }
            }
        }

        $page = $pdf->drawLineBlocks($page, $drawItems, array('table_header' => true));
        $this->setPage($page);
    }
}
