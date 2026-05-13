<?php
namespace Vnecoms\VendorsProductImportExport\Model\Import\Product\Type;

use Vnecoms\VendorsProductImportExport\Model\ResourceModel\Import\Data\Collection as ImportSource;

class Bundle extends \Magento\BundleImportExport\Model\Import\Product\Type\Bundle implements TypeInterface
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
    
    /**
     * Save product type specific data.
     *
     * @return \Magento\CatalogImportExport\Model\Import\Product\Type\AbstractType
     */
    public function saveData()
    {
        /* if ($this->_entityModel->getBehavior() == \Magento\ImportExport\Model\Import::BEHAVIOR_DELETE) {
            $productIds = [];
            $newSku = $this->_entityModel->getNewSku();
            while ($bunch = $this->_entityModel->getNextBunch()) {
                foreach ($bunch as $rowNum => $rowData) {
                    $productData = $newSku[$rowData[\Magento\CatalogImportExport\Model\Import\Product::COL_SKU]];
                    $productIds[] = $productData[$this->getProductEntityLinkField()];
                }
                $this->deleteOptionsAndSelections($productIds);
            }
        } else {
            $newSku = $this->_entityModel->getNewSku();
            while ($bunch = $this->_entityModel->getNextBunch()) {
                foreach ($bunch as $rowNum => $rowData) {
                    if (!$this->_entityModel->isRowAllowedToImport($rowData, $rowNum)) {
                        continue;
                    }
                    $productData = $newSku[$rowData[\Magento\CatalogImportExport\Model\Import\Product::COL_SKU]];
                    if ($this->_type != $productData['type_id']) {
                        continue;
                    }
                    $this->parseSelections($rowData, $productData[$this->getProductEntityLinkField()]);
                }
                if (!empty($this->_cachedOptions)) {
                    $this->retrieveProducsByCachedSkus();
                    $this->populateExistingOptions();
                    $this->insertOptions();
                    $this->insertSelections();
                    $this->clear();
                }
            }
        } */
        return $this;
    }
}
