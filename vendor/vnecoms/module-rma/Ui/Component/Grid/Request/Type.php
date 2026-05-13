<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

// @codingStandardsIgnoreFile

namespace Vnecoms\RMA\Ui\Component\Grid\Request;
use Magento\Framework\Data\OptionSourceInterface;

class Type implements OptionSourceInterface
{

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->_scopeConfig = $scopeConfig;
    }

    /**
     * Get types methods
     *
     * @throws \Exception
     * @return multitype:\Magento\Framework\mixed
     */
    public function getTypeMethods(){
        $methodConfig = $this->_scopeConfig->getValue('rma_type_methods');
        $typesMethods = [];
        foreach($methodConfig as $code => $config){
            if(!isset($config['active']) || !$config['active']) continue;
            $typesMethods[] = array("label"=> __($config['title']),"value"=>$code);
        }
        return $typesMethods;
    }

    public function toOptionArray()
    {
        return $this->getTypeMethods();
    }

    public function getOptionArray()
    {
        $methodConfig = $this->_scopeConfig->getValue('rma_type_methods');
        $typesMethods = [];
        foreach($methodConfig as $code => $config){
            if(!isset($config['active']) || !$config['active']) continue;
            $typesMethods[$code] = __($config['title']);
        }
        return $typesMethods;
    }

    public function getLableByCode($code)
    {
        $typesMethods = $this->getOptionArray();
        return $typesMethods[$code] ? $typesMethods[$code] : "";
    }

}
