<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsShippingTableRate\Model\Carrier;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\Result;

/**
 * Tablerate shipping model
 */
class Tablerate extends AbstractCarrier implements CarrierInterface
{

    CONST CONDITION_WEIGHT_DESTINATION      = 'package_weight';
    CONST CONDITION_PRICE_DESTINATION       = 'package_value';
    CONST CONDITION_QTY_DESTINATION         = 'package_qty';
    /**
     * @var string
     */
    protected $_code = 'vtablerate';


    /**
     * @var \Magento\Shipping\Model\Rate\ResultFactory
     */
    protected $_rateResultFactory;

    /**
     * @var \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory
     */
    protected $_rateMethodFactory;

    /**
     * @var \Vnecoms\VendorsConfig\Helper\Data
     */
    protected $_vendorConfig;


    /**
     * @var \Vnecoms\VendorsShippingTableRate\Model\ResourceModel\Tablerate
     */
    protected $_resourceTable;

    /**
     * @var \Vnecoms\VendorsShippingTableRate\Model\Source\Convert
     */
    protected $_convert;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory
     * @param \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory
     * @param ItemPriceCalculator $itemPriceCalculator
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        \Vnecoms\VendorsConfig\Helper\Data $vendorConfig,
        \Vnecoms\VendorsShippingTableRate\Model\ResourceModel\Tablerate $resource,
        \Vnecoms\VendorsShippingTableRate\Model\Source\Convert $convert,
        array $data = []
    ) {
        $this->_rateResultFactory = $rateResultFactory;
        $this->_rateMethodFactory = $rateMethodFactory;
        $this->_vendorConfig = $vendorConfig;
        $this->_resourceTable = $resource;
        $this->_convert = $convert;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    /**
     * Retrieve information from carrier configuration
     *
     * @param   string $field
     * @param   string $vendor_id
     * @return  mixed
     */
    public function getVendorConfigData($field, $vendorId)
    {
        /**
         * field = {free_shipping_subtotal; price; type}
         */
        $path = 'shipping_method/tablerate/'.$field;
        return $this->_vendorConfig->getVendorConfig($path,$vendorId);
    }

    /**
     * Group Items by vendor
     * @return Ambigous <multitype:multitype: , unknown>
     */
    public function groupItemsByVendor($request){
        $quotes = array();
        foreach($request->getAllItems() as $item) {
            $product    = $item->getProduct()->load($item->getProductId());
            if($item->getParentItem() || $product->isVirtual()) continue;
            if($item->getProduct()->getVendorId()) {
                if($item->getVendorId()){
                    $vendorId = $item->getVendorId();
                }else{
                    $vendorId = $item->getProduct()->getVendorId();
                }
                $om  = \Magento\Framework\App\ObjectManager::getInstance();
                $transport = new \Magento\Framework\DataObject(array('vendor_id'=>$vendorId,'item'=>$item));
                $eventManager = $om->create('\Magento\Framework\Event\ManagerInterface');

                $eventManager->dispatch('ves_vendors_checkout_init_vendor_id',['transport' => $transport]);

                $vendorId = $transport->getVendorId();

                /*Get item by vendor id*/
                if(!isset($quotes[$vendorId])) $quotes[$vendorId] = [];
                $quotes[$vendorId][] = $item;
            } else {
                $quotes['no_vendor'][] = $item;
            }
        }
        return $quotes;
    }

    /**
     * @param RateRequest $request
     * @param \Magento\Framework\DataObject $additionalRequest
     * @param array $items
     * @return array|bool
     */
    public function getRate(RateRequest $request, \Magento\Framework\DataObject $additionalRequest,  $items= [])
    {
        return $this->_resourceTable->getRate($request,$additionalRequest, $items);
    }

    /**
     * Format Key for URL
     *
     * @param string $str
     * @return string
     */
    public function formatMethodName($str)
    {
        $methodName = preg_replace('#[^0-9a-z]+#i', '_', $this->_convert->format($str));
        $methodName = strtolower($methodName);
        $methodName = trim($methodName, '_');
        return $methodName;
    }


    /**
     * @param RateRequest $request
     * @return Result|bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function collectRates(RateRequest $request)
    {
        /*The current method and multiple rate mehtod must to be both activated*/
        if (!$this->getConfigFlag('active') ||
            !$this->_scopeConfig->isSetFlag(
                'carriers/vendor_multirate/active',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $this->getStore()
            )
        ) {
            return false;
        }


        $om  = \Magento\Framework\App\ObjectManager::getInstance();

        $quotes = $this->groupItemsByVendor($request);
        $vendorRates = array();
        $result = $this->_rateResultFactory->create();

        /*
         * seperate each vendor item to dependence array in quotes array.
         */
        $desCountryId = $request->getDestCountryId();

        foreach($quotes as $vendorId=>$items){
            if(!$this->getVendorConfigData("active",$vendorId)) continue;
            $condition = $this->getVendorConfigData('condition',$vendorId);

            $conditionValue = 0;

            $hasFreeShiping = 0;
            switch ($condition){
                case self::CONDITION_WEIGHT_DESTINATION:
                    $weight = 0;
                    foreach($items as $item){
                        if ($item->getFreeShipping()) {
                            $hasFreeShiping ++;
                            continue;
                        }
                        $weight += $item->getWeight() * $item->getQty();
                    }
                    $weight = $this->getTotalNumOfBoxes($weight);
                    $conditionValue = $weight;
                    break;
                case self::CONDITION_PRICE_DESTINATION:
                    $rowTotal = 0;
                    foreach($items as $item){
                        if ($item->getFreeShipping()) {
                            $hasFreeShiping ++;
                            continue;
                        }
                        $rowTotal+= $item->getRowTotal();
                    }
                    $conditionValue = $rowTotal;
                    break;
                case self::CONDITION_QTY_DESTINATION:
                    $totalQty = 0;
                    foreach($items as $item){
                        if ($item->getFreeShipping()) {
                            $hasFreeShiping ++;
                            continue;
                        }
                        $totalQty+= $item->getQty();
                    }
                    $conditionValue = $totalQty;
                    break;
            }

            $additionalRequest = new \Magento\Framework\DataObject(array(
                'condition_name'    => $condition,
                'condition_value'   => $conditionValue,
                'vendor_id'         => $vendorId,
            ));

            $rates = $this->getRate($request,$additionalRequest, $items);


            if (!empty($rates) && is_array($rates)) {
                foreach($rates as $rate){
                    /** @var \Magento\Quote\Model\Quote\Address\RateResult\Method $method */
                    $method = $this->_rateMethodFactory->create();
                    $method->setCarrier($this->_code);
                    $method->setCarrierTitle($this->getConfigData('title'));
                    $method->setVendorId($vendorId);

                    $method->setMethod($this->formatMethodName($rate['delivery_type']).$rate['rate_id'].\Vnecoms\VendorsShipping\Plugin\Shipping::SEPARATOR.$vendorId);
                    $method->setMethodTitle($rate['delivery_type']);

                    if ($request->getFreeShipping() === true ||
                        $hasFreeShiping == count($items)
                    ) {
                        $shippingPrice = 0;
                    } else {
                        /*shippingPrice = $this->getFinalPriceWithHandlingFee($rate['price']);*/
                        $shippingPrice = $rate['price'];
                    }

                    $method->setPrice($shippingPrice);
                    $method->setCost($shippingPrice);

                    $result->append($method);
                }
            }
        }
        return $result;
    }

    /**
     * @return bool
     */
    public function isTrackingAvailable() {
        return false;
    }

    /**
     * @return array
     */
    public function getAllowedMethods()
    {
        return ['vtablerate' => $this->getConfigData('name')];
    }
}
