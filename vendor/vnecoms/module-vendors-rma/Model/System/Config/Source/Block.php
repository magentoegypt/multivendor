<?php
/**
 * Created by PhpStorm.
 * User: nvhai
 * Date: 12/28/2016
 * Time: 10:03 AM
 */
namespace Vnecoms\VendorsRMA\Model\System\Config\Source;

class Block implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options array
     *
     * @var array
     */
    protected $_options = null;
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray(){
        if ($this->_options === null) {
            $this->_options = [];
            $om = \Magento\Framework\App\ObjectManager::getInstance();
            $sessionVendor = $om->get('\Vnecoms\Vendors\Model\Session');
            $vendorId = $sessionVendor->getVendor()->getId();
            $module = $om->create('Magento\Framework\Module\Manager');

            if ($module->isEnabled("Vnecoms_VendorsCms")) {
                $blockCollection = $om->create('Vnecoms\VendorsCms\Model\Block')->load($vendorId)->getCollection();
                foreach ($blockCollection as $group) {
                    $this->_options[] = ['label' => $group->getTitle(), 'value' => $group->getBlockId()];
                }
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
        foreach ($this->toOptionArray() as $option) {
            $_options[$option['value']] = $option['label'];
        }
        return $_options;
    }

}