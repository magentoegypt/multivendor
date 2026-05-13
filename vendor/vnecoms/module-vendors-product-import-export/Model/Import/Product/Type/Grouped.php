<?php
namespace Vnecoms\VendorsProductImportExport\Model\Import\Product\Type;

use Vnecoms\VendorsProductImportExport\Model\ResourceModel\Import\Data\Collection as ImportSource;
use Vnecoms\VendorsProductImportExport\Model\Import\Product as ImportProduct;
use Magento\ImportExport\Model\Import as Import;

class Grouped extends \Magento\GroupedImportExport\Model\Import\Product\Type\Grouped implements TypeInterface
{
    /**
     * @var ImportSource
     */
    protected $_importSource;

        /**
     * Product entity identifier field
     *
     * @var string
     */
    private $vendorProductEntityIdentifierField;
    
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
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function saveData()
    {

        $newSku = $this->_entityModel->getNewSku();
        $oldSku = $this->_entityModel->getOldSku();
        $attributes = $this->links->getAttributes();
        $productData = [];
        $source = $this->getSource();

        foreach ($source as $row) {
            if ($row->getBehavior() != \Vnecoms\VendorsProductImportExport\Model\Import::BEHAVIOR_APPEND) {
                continue;
            }
            $linksData = [
                'product_ids' => [],
                'attr_product_ids' => [],
                'position' => [],
                'qty' => [],
                'relation' => []
            ];

            $rowData = json_decode($row->getProductData(), true);
            $rowData[ImportProduct::COL_SKU] = $row->getSku();

            if ($this->_type != $rowData['product_type']) {
                    continue;
            }
            $associatedSkusQty = isset($rowData['associated_skus']) ? $rowData['associated_skus'] : null;

            if (!$this->_entityModel->isRowAllowedToImport($rowData, $row->getId()) || empty($associatedSkusQty)) {
                continue;
            }
            $associatedSkusAndQtyPairs = explode(Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR, $associatedSkusQty);
            $position = 0;
            foreach ($associatedSkusAndQtyPairs as $associatedSkuAndQty) {
                ++$position;
                $associatedSkuAndQty = explode(self::SKU_QTY_DELIMITER, $associatedSkuAndQty);
                $associatedSku = isset($associatedSkuAndQty[0]) ? trim($associatedSkuAndQty[0]) : null;
                $associatedSku = strtolower($associatedSku);

                if (isset($newSku[$associatedSku])) {
                    $linkedProductId = $newSku[$associatedSku][$this->getVendorProductEntityIdentifierField()];
                } elseif (isset($oldSku[$associatedSku])) {
                    $linkedProductId = $oldSku[$associatedSku][$this->getVendorProductEntityIdentifierField()];
                } else {
                    continue;
                }
                $scope = $this->_entityModel->getRowScope($rowData);
                if (ImportProduct::SCOPE_DEFAULT == $scope) {
                    $sku = strtolower($rowData[ImportProduct::COL_SKU]);
                    $productData = $newSku[$sku];
                } else {
                    $colAttrSet = Product::COL_ATTR_SET;
                    $rowData[$colAttrSet] = $productData['attr_set_code'];
                    $rowData[ImportProduct::COL_TYPE] = $productData['type_id'];
                }
                $productId = $productData[$this->getProductEntityLinkField()];

                $linksData['product_ids'][$productId] = true;
                $linksData['relation'][] = ['parent_id' => $productId, 'child_id' => $linkedProductId];
                $qty = empty($associatedSkuAndQty[1]) ? 0 : trim($associatedSkuAndQty[1]);
                $linksData['attr_product_ids'][$productId] = true;
                $linksData['position']["{$productId} {$linkedProductId}"] = [
                    'product_link_attribute_id' => $attributes['position']['id'],
                    'value' => $position
                ];
                if ($qty) {
                    $linksData['attr_product_ids'][$productId] = true;
                    $linksData['qty']["{$productId} {$linkedProductId}"] = [
                        'product_link_attribute_id' => $attributes['qty']['id'],
                        'value' => $qty
                    ];
                }
            }
            $this->links->saveLinksData($linksData);
        }
        return $this;
    }

     /**
     * Get product entity identifier field
     *
     * @return string
     */
    private function getVendorProductEntityIdentifierField()
    {
        if (!$this->vendorProductEntityIdentifierField) {
            $this->vendorProductEntityIdentifierField = $this->getMetadataPool()
                ->getMetadata(\Magento\Catalog\Api\Data\ProductInterface::class)
                ->getIdentifierField();
        }
        return $this->vendorProductEntityIdentifierField;
    }
}
