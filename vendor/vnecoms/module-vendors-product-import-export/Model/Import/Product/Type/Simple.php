<?php
namespace Vnecoms\VendorsProductImportExport\Model\Import\Product\Type;

use Vnecoms\VendorsProductImportExport\Model\ResourceModel\Import\Data\Collection as ImportSource;

class Simple extends \Magento\CatalogImportExport\Model\Import\Product\Type\Simple implements TypeInterface
{
    /**
     * @var ImportSource
     */
    protected $_importSource;
    
    /**
     * Set Source
     *
     * @param ImportSource $source
     * @return \Vnecoms\VendorsProductImportExport\Model\Import\Product\Type\Bundle
     */
    public function setSource(ImportSource $source){
        $this->_importSource = $source;
        return $this;
    }
    
    /**
     * Get Source
     *
     * @return \Vnecoms\VendorsProductImportExport\Model\ResourceModel\Import\Data\Collection
     */
    public function getSource(){
        return $this->_importSource;
    }
    
    
}
