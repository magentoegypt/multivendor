<?php

namespace Vnecoms\VendorsLayerNavigation\Model\ResourceModel\Layer\Filter;

class Price extends \Magento\Catalog\Model\ResourceModel\Layer\Filter\Price
{
    protected function getAllowState($reFormat=false)
    {
        $om = \Magento\Framework\App\ObjectManager::getInstance();
        $allowState = $om->create('Vnecoms\VendorsProduct\Helper\Data')->getAllowedApprovalStatus();

        if($reFormat) {
            return implode(', ', $allowState);
        }

        return $allowState;
    }

    protected function getJoinAttributeProduct($reFormat=false)
    {
        $om = \Magento\Framework\App\ObjectManager::getInstance();
        $attributes = $om->create('Vnecoms\VendorsProduct\Helper\Data')->getJoinProductAttribute();

        if($reFormat) {
            return implode(', ', $attributes);
        }

        return $attributes;
    }

    /**
     * Retrieve clean select with joined price index table
     *
     * @return \Magento\Framework\DB\Select
     */
    protected function _getSelect()
    {
        $select = parent::_getSelect();
        $fromPart = $select->getPart(\Magento\Framework\DB\Select::FROM);
        $wherePart = $select->getPart(\Magento\Framework\DB\Select::WHERE);
        $originJoinAttribute  = $this->getJoinAttributeProduct();
        $joinAttribute = [];
        foreach($originJoinAttribute as $attributeCode => $type){
            if($type != 'static' && isset($fromPart['at_'.$attributeCode])) continue;
            $joinAttribute[$attributeCode] = $type;
        }

        $newJoinAttribute = [];
        //remove vendor part of where part
        foreach($wherePart as $id => $where) {
            foreach ($joinAttribute as $attribute => $type) {

                if(preg_match("/\`".$attribute."\`/is",$where)){
                    unset($wherePart[$id]);
                    $newJoinAttribute[$attribute] = ["type"=>$type , "query" => $where];
                }

                if(preg_match("/".$attribute."/is",$where)){
                    unset($wherePart[$id]);
                    $newJoinAttribute[$attribute] = ["type"=>$type , "query" => $where];
                }

            }
        }
				
				/*
        $flatMode = \Magento\Framework\App\ObjectManager::getInstance()
            ->get('Magento\Framework\App\Config\ScopeConfigInterface')
            ->getValue('catalog/frontend/flat_catalog_product');
        if($flatMode)
        {
            $firstWhere = current($wherePart);
            $wherePart[key($wherePart)] = trim($firstWhere, 'AND ');
        }
				*/

        $firstWhere = current($wherePart);
        $wherePart[key($wherePart)] = trim(trim($firstWhere, 'AND'));

        $select->reset(\Magento\Framework\DB\Select::WHERE);

        $select->setPart(\Magento\Framework\DB\Select::WHERE, $wherePart);
        foreach ($newJoinAttribute as $attribute => $data) {
            switch ($data["type"]) {
                case 'static':
                    if(!isset($fromPart['product_entity'])){

                        if(preg_match("/\`".$attribute."\`/is",$data["query"])){
                            $where = preg_replace("/\`".$attribute."\`/is", "product_entity.".$attribute , $data["query"] );
                        }else{
                            if(preg_match("/".$attribute."/is",$data["query"])){
                                $where = preg_replace("/".$attribute."/is", "product_entity.".$attribute , $data["query"] );
                            }
                        }

                        $where = preg_replace("/\`e\`\./is","",$where);
                        $where = preg_replace("/e\./is","",$where);

                        $where = trim(trim($where,"AND"));
												
                        $select->join(
                            ['product_entity'=>$this->getTable('catalog_product_entity')],
                            "product_entity.entity_id = e.entity_id AND ".$where,
                            []
                        );
                    }
                    break;
                default:
                    $prefix = "at_".$attribute;

                    if(!isset($fromPart[$prefix])) {

                        if(preg_match("/\`".$attribute."\`/is",$data["query"])){
                            $where = preg_replace("/\`".$attribute."\`/is", $prefix.".value" , $data["query"] );
                        }else{
                            if(preg_match("/".$attribute."/is",$data["query"])){
                                $where = preg_replace("/".$attribute."/is", $prefix.".value" , $data["query"] );
                            }
                        }
                        $where = preg_replace("/e\./is","",$where);
                        $where = preg_replace("/\`e\`\./is","",$where);
                        $where = trim(trim($where,"AND"));

                        $select->join(
                            [ $prefix =>$this->getTable('catalog_product_entity_'.$data["type"])],
                            $prefix.".entity_id = e.entity_id AND ".$prefix.".attribute_id = '".$this->getIdOfAttributeCode('catalog_product',$attribute)."'"
                            ." AND ".$where, //@todo dont know why need to 0
                            []
                        );
                    }

                    break;
            }
        }

        return $select;
    }

    public function getIdOfAttributeCode($entityCode, $code)
    {
        return \Magento\Framework\App\ObjectManager::getInstance()
            ->get('Magento\Eav\Model\ResourceModel\Entity\Attribute')
            ->getIdByCode($entityCode,$code);
    }
}
