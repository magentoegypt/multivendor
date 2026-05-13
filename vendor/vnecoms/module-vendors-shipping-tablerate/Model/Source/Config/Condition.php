<?php


namespace Vnecoms\VendorsShippingTableRate\Model\Source\Config;

class Condition extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource

{

    /**
     * Options array
     *
     * @var array
     */
    protected $_options = null;

    /**
     * Retrieve all options array
     *
     * @return array
     */
    public function getAllOptions()
    {
        if ($this->_options === null) {
                $this->_options =  [
                ['value' => \Vnecoms\VendorsShippingTableRate\Model\Carrier\Tablerate::CONDITION_WEIGHT_DESTINATION ,
                    'label' => __('Weight vs. Destination')],
                ['value' => \Vnecoms\VendorsShippingTableRate\Model\Carrier\Tablerate::CONDITION_PRICE_DESTINATION,
                    'label' => __('Price vs. Destination')],
                ['value' => \Vnecoms\VendorsShippingTableRate\Model\Carrier\Tablerate::CONDITION_QTY_DESTINATION,
                    'label' => __('# of Items vs. Destination')],
            ];
        }
        return $this->_options;
    }

    /**
     * Options getterRussian
     *
     * @return array
     */
    public function toOptionArray()
    {
        return $this->getAllOptions();
    }


    /**
     * Retrieve option array
     *
     * @return array
     */
    public function getOptionArray()
    {
        $_options = [];
        foreach ($this->toOptionArray() as $option) {
            $_options[$option['value']] = $option['label'];
        }
        return $_options;
    }


}
