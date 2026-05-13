<?php

namespace Vnecoms\VendorsPriceComparison\Model\Source\Product;

class Main extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    const TYPE_FIRST_VENDOR = 'first_vendor';
    const TYPE_LOWEST_PRICE = 'lowest_price';

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
            $this->_options = [
                ['label' => __("The product from first vendor who created it"), 'value' => self::TYPE_FIRST_VENDOR],
                ['label' => __("The lowest price product"), 'value' => self::TYPE_LOWEST_PRICE],
            ];
        }
        return $this->_options;
    }

    /**
     * Retrieve option array
     *
     * @return array
     */
    public function getOptionArray()
    {
        $_options = [];
        foreach ($this->getAllOptions() as $option) {
            $_options[$option['value']] = $option['label'];
        }
        return $_options;
    }


    /**
     * Get options as array
     *
     * @return array
     * @codeCoverageIgnore
     */
    public function toOptionArray()
    {
        return $this->getAllOptions();
    }

}
