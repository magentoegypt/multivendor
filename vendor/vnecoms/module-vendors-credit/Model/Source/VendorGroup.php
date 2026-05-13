<?php

namespace Vnecoms\VendorsCredit\Model\Source;

class VendorGroup extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{

    /**
     * @var \Vnecoms\Vendors\Model\ResourceModel\Group\Collection
     */
    protected $attrCollection;

    /**
     * VendorGroup constructor.
     * @param \Vnecoms\Vendors\Model\ResourceModel\Group\Collection $collection
     */
    public function __construct(
        \Vnecoms\Vendors\Model\ResourceModel\Group\Collection $collection
    ) {
        $this->attrCollection = $collection;
    }

    /**
     * Retrieve all options array
     *
     * @return array
     */
    public function getAllOptions()
    {
        if ($this->_options === null) {
            foreach ($this->attrCollection as $attr) {
                $this->_options[] = ['label' => $attr->getVendorGroupCode(), 'value' => $attr->getId()];
            }
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
