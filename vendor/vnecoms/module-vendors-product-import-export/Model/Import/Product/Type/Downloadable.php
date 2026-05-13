<?php
namespace Vnecoms\VendorsProductImportExport\Model\Import\Product\Type;

use Vnecoms\VendorsProductImportExport\Model\ResourceModel\Import\Data\Collection as ImportSource;
use Vnecoms\VendorsProductImportExport\Model\Import\Product as ImportProduct;

class Downloadable extends \Magento\DownloadableImportExport\Model\Import\Product\Type\Downloadable implements TypeInterface
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
        $newSku = $this->_entityModel->getNewSku();

        $source = $this->getSource();

        foreach ($source as $row) {
            if ($row->getBehavior() != \Vnecoms\VendorsProductImportExport\Model\Import::BEHAVIOR_APPEND) {
                continue;
            }

            $rowData = json_decode($row->getProductData(), true);
            $rowData[ImportProduct::COL_SKU] = $row->getSku();

            if (!$this->_entityModel->isRowAllowedToImport($rowData,  $row->getId())) {
                    continue;
            }

            $sku = strtolower($rowData[ImportProduct::COL_SKU]);

            $productData = $newSku[$sku];
            if ($this->_type != $productData['type_id']) {
                continue;
            }
            $this->parseOptions($rowData, $productData[$this->getProductEntityLinkField()]);

        }
        if (!empty($this->cachedOptions['sample']) || !empty($this->cachedOptions['link'])) {
            $this->saveOptions();
            $this->clear();
        }
        return $this;
    }
}
