<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsProduct\Model\Product;

use Magento\Framework\Event\ManagerInterface as EventManagerInterface;

class ProcessUpdateAttribute
{
    const IGNORE_TEXT_LENGTH = 1;

    /**
     * Vendor Product helper
     * @var \Vnecoms\VendorsProduct\Helper\Data
     */
    protected $vendorProductHelper;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection
     */
    protected $productAttribute;

    /**
     * @var EventManagerInterface
     */
    protected $_eventManager;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Serialize
     */
    protected $serializer;

    /**
     * ProcessUpdateAttribute constructor.
     * @param \Vnecoms\VendorsProduct\Helper\Data $helper
     * @param \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $collection
     * @param EventManagerInterface $eventManager
     * @param \Magento\Framework\Serialize\Serializer\Serialize $serializer
     */
    public function __construct(
        \Vnecoms\VendorsProduct\Helper\Data $helper,
        \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $collection,
        EventManagerInterface $eventManager,
        \Magento\Framework\Serialize\Serializer\Serialize $serializer
    )
    {
        $this->vendorProductHelper = $helper;
        $this->productAttribute = $collection;
        $this->_eventManager = $eventManager;
        $this->serializer = $serializer;
        return $this;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return array
     */
    public function getChangedData(\Magento\Catalog\Model\Product $product) {
        $changedData = [];
        $productAttrCollection = $this->productAttribute->create()->addVisibleFilter();
        $notUsedProductAttr = $this->vendorProductHelper->getNotUsedVendorAttributes();

        foreach ($productAttrCollection as $attribute) {
            $attrCode = $attribute->getAttributeCode();
            $attrType = $attribute->getBackendType();
            if (
                in_array($attrCode, $notUsedProductAttr) ||
                $attribute->getData("is_pagebuilder_enabled")
            ) {
                continue;
            }

            $newData = $product->getData($attrCode);
            $coreData = $newData;
            if ($attrCode == "tier_price" && is_array($newData)) {
                foreach ($newData as $key => &$data) {
                    if (isset($data["delete"]) && $data["delete"] == 1) {
                        unset($newData[$key]);
                    }
                    if (isset($data["initialize"])) {
                        unset($data["initialize"]);
                    }
                    if (isset($data["record_id"])) {
                        unset($data["record_id"]);
                    }
                    if (isset($data["value_type"])) {
                        unset($data["value_type"]);
                    }
                }
            }

            if ($this->compareAttributeValue($attrCode, $attrType, $newData, $product->getOrigData($attrCode))) {
                $changedData[$attrCode] = $coreData;
            }
        }
        return $changedData;
    }


    /**
     * Compare two attribute value
     * return true if they are different.
     *
     * @param string $attrCode
     * @param mixed|null|string $attrType
     * @param array $data
     * @param array $originData
     * @return bool
     */
    private function compareAttributeValue($attrCode, $attrType, $data, $originData)
    {
        $result = false;
        if (!$this->vendorProductHelper->getUpdateProductsApprovalFlag()) {
            $notCheckAttributes = $this->vendorProductHelper->getIgnoreUpdateApprovalProductAttributes();
            $notCheckAttributes = array_merge($this->vendorProductHelper->getUpdateProductsApprovalAttributes(),$notCheckAttributes);
            if (is_array($data) &&
                !in_array($attrCode, $notCheckAttributes)
            ) {
                if ($originData) {
                    if (!is_array($originData)) {
                        $originData = explode(',', $originData);
                    }

                    if (sizeof($data) <= 0 && sizeof($originData) > 0) {
                        $result = true;
                    } else {
                        $diff = $this->_multi_diff($data, $originData);
                        $result = sizeof($diff['more']) || sizeof($diff['diff']);
                    }
                }
            } else {
                switch ($attrType) {
                    case "decimal":

                        if ($originData) {
                            $originData = str_replace(',', '', $originData);
                            if (is_numeric($originData)) {
                                $originData = number_format($originData, 2, '.', ',');
                            }
                        }

                        if ($data) {
                            $data = str_replace(',', '', $data);
                            if (is_numeric($data)) {
                                $data = number_format($data, 2, '.', ',');
                            }
                        }

                        $result = ($data !== false) && ($data !== null) && ($data != $originData);
                        break;
                    case "text":
                        if (is_array($data)) {
                            $data = $this->serializer->serialize($data);
                        }
                        if (is_array($originData)) {
                            $originData = $this->serializer->serialize($originData);
                        }
                        if ($data) {
                            $data = trim($data);
                        }
                        if ($originData) {
                            $originData = trim($originData);
                        }
                        if ($data && $originData) {
                            $isCheck = strcmp($data, $originData) > self::IGNORE_TEXT_LENGTH || strcmp($originData, $data) > self::IGNORE_TEXT_LENGTH;
                        } else {
                            $isCheck = true;
                        }
                        $result = ($data !== false) && ($data !== null) && $isCheck;
                        break;
                    default:
                        $result = ($data !== false) && ($data !== null) && ($data != $originData);
                        break;
                }
            }

            $additionalCompare = false;
            if (in_array($attrCode, $notCheckAttributes)) {
                /*Ignore checking value changes*/
            } else {
                $additionalCompare = true;
            }
        } else {
            $checkAttributes = $this->vendorProductHelper->getUpdateProductsApprovalAttributes();
            $additionalCompare = false;
            $result = false;
            if (in_array($attrCode, $checkAttributes)) {
                if (is_array($data)) {
                    if ($originData) {
                        if (!is_array($originData)) {
                            $originData = explode(',', $originData);
                        }

                        if (sizeof($data) <= 0 && sizeof($originData) > 0) {
                            $result = true;
                        } else {
                            $diff = $this->_multi_diff($data, $originData);
                            $result = sizeof($diff['more']) || sizeof($diff['diff']);
                        }
                    }
                } else {
                    switch ($attrType) {
                        case "decimal":
                            if ($originData) {
                                $originData = str_replace(',', '', $originData);
                                if(is_numeric($originData)) {
                                    $originData = number_format($originData, 2, '.', ',');
                                }
                            }
                            if ($data) {
                                $data = str_replace(',', '', $data);
                                if(is_numeric($data)) {
                                    $data = number_format($data, 2, '.', ',');
                                }
                            }
                            break;
                        case "text":
                            if (is_array($data)) {
                                $data = $this->serializer->serialize($data);
                            }
                            if (is_array($originData)) {
                                $originData = $this->serializer->serialize($originData);
                            }
                            if ($data) {
                                $data = trim($data);
                            }
                            if ($originData) {
                                $originData = trim($originData);
                            }
                            if ($data && $originData) {
                                $isCheck = strcmp($data, $originData) > self::IGNORE_TEXT_LENGTH || strcmp($originData, $data) > self::IGNORE_TEXT_LENGTH;
                            } else {
                                $isCheck = true;
                            }
                            $result = ($data !== false) && ($data !== null) && $isCheck;
                            break;
                        default:
                            $result = ($data !== false) && ($data !== null) && ($data != $originData);
                            break;
                    }
                }

                $additionalCompare = true;
            }
        }

        $transport = new \Magento\Framework\DataObject([
            'attribute_code' => $attrCode,
            'new_data' => $data,
            'origin_data' => $originData,
            'compare' => $additionalCompare,
        ]);

        $this->_eventManager->dispatch('vnecoms_vendorsproduct_compare_attribute_value', ['transport' => $transport]);

        $additionalCompare = $transport->getData('compare');

        return $result && $additionalCompare;
    }

    /**
     * @param $array1
     * @param $array2
     * @return array
     */
    private function _multi_diff($array1, $array2){
        $result = array("more"=>array(),"less"=>array(),"diff"=>array());
        foreach($array1 as $k => $v) {
            if(is_array($v) && isset($array2[$k]) && is_array($array2[$k])){
                $sub_result = $this->_multi_diff($v, $array2[$k]);
                //merge results
                foreach(array_keys($sub_result) as $key){
                    if(!empty($sub_result[$key])){
                        $result[$key] = array_merge_recursive($result[$key],array($k => $sub_result[$key]));
                    }
                }
            }else{
                if(array_key_exists($k, $array2)){
                    $from = $v;
                    if ($this->_is_decimal_value($from)) {
                        $from = str_replace(',', '', $from);
                        if(is_numeric($from)) {
                            $from = number_format($from, 2, '.', ',');
                        }
                    }

                    $to = $array2[$k];
                    if ($this->_is_decimal_value($to)) {
                        $to = str_replace(',', '', $to);
                        if(is_numeric($to)) {
                            $to = number_format($to, 2, '.', ',');
                        }
                    }

                    if($from != $to){
                        $result["diff"][$k] = array("from"=>$from,"to"=> $to);
                    }
                }else{
                    $result["more"][$k] = $v;
                }
            }
        }
        foreach($array2 as $k => $v) {
            if(!array_key_exists($k, $array1)){
                $result["less"][$k] = $v;
            }
        }
        return $result;
    }

    /**
     * @param $a
     * @return bool
     */
    private function _is_decimal_value( $a ) {
        $d=0; $i=0;
        $b= str_split(trim($a.""));
        foreach ( $b as $c ) {
            if ( $i==0 && strpos($c,"-") !== false ) continue;
            $i++;
            if ( is_numeric($c) ) continue;
            if ( stripos($c,".") === false ) {
                $d++;
                if ( $d > 1 ) return FALSE;
                else continue;
            } else
                return FALSE;
        }
        return TRUE;
    }
}
