<?php
namespace Vnecoms\VendorsProductImportExport\Model\Import\Product\Type;

use Vnecoms\VendorsProductImportExport\Model\ResourceModel\Import\Data\Collection as ImportSource;
use Magento\Catalog\Api\Data\ProductInterface;
use Vnecoms\VendorsProductImportExport\Model\Import\Product as ImportProduct;
use Magento\Framework\EntityManager\MetadataPool;

class Configurable extends \Magento\ConfigurableImportExport\Model\Import\Product\Type\Configurable implements TypeInterface
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
    public function setSource(ImportSource $source)
    {
        $this->_importSource = $source;
        return $this;
    }

    /**
     * Get Source
     *
     * @return \Vnecoms\VendorsProductImportExport\Model\ResourceModel\Import\Data\Collection
     */
    public function getSource()
    {
        return $this->_importSource;
    }

    /**
     * Save product type specific data.
     *
     * @throws \Exception
     * @return \Magento\CatalogImportExport\Model\Import\Product\Type\AbstractType
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function saveData()
    {
        $newSku = $this->_entityModel->getNewSku();
        $oldSku = $this->_entityModel->getOldSku();

        $this->_productSuperData = [];
        $this->_productData = null;
        $source = $this->getSource();

        foreach ($source as $row) {
            if ($row->getBehavior() == \Vnecoms\VendorsProductImportExport\Model\Import::BEHAVIOR_APPEND) {
                $this->_loadSkuSuperDataForRow($row);
            }else{
                continue;
            }

            if (!$this->configurableInDataImported($row)) {
                continue;
            }

            $rowData = json_decode($row->getProductData(), true);
            $rowData[ImportProduct::COL_SKU] = $row->getSku();
            $this->_superAttributesData = [
                'attributes' => [],
                'labels' => [],
                'super_link' => [],
                'relation' => [],
            ];

            $this->_simpleIdsToDelete = [];

            $this->_loadSkuSuperAttributeValuesByRow($rowData, $newSku, $oldSku);
              // remember SCOPE_DEFAULT row data
            if ($this->_entityModel->isRowAllowedToImport($rowData, $row->getId())) {
                $scope = $this->_entityModel->getRowScope($rowData);
                if (ImportProduct::SCOPE_DEFAULT == $scope &&
                    $row->getSku()) {
                    $sku = strtolower($row->getSku());
                    $this->_productData = isset($newSku[$sku]) ? $newSku[$sku] : $oldSku[$sku];
                    if ($this->_type != $this->_productData['type_id']) {
                        $this->_productData = null;
                        continue;
                    }
                    $this->_collectSuperData($rowData);
                }
            }
           // save last product super data
            $this->_processSuperData();

            $this->_deleteData();

            $this->_insertData();
        }
        return $this;
    }


    /**
     * Configurable in bunch
     *
     * @param array $row
     * @return bool
     */
    protected function configurableInDataImported($row)
    {
        $rowData = json_decode($row->getProductData(), true);
        $rowData[ImportProduct::COL_SKU] = $row->getSku();
        if (($this->_type == $rowData['product_type']) && ($rowData == $this->_entityModel
                    ->isRowAllowedToImport($rowData, $row->getId()))) {
            return true;
        }
        return false;
    }

    /**
     * Array of SKU to array of super attribute values for all products.
     *
     * @return $this
     */
    protected function _loadSkuSuperDataForRow($row)
    {
        $newSku = $this->_entityModel->getNewSku();
        $oldSku = $this->_entityModel->getOldSku();
        $productIds = [];
        $sku = strtolower($row->getSku());
        $productData = isset($newSku[$sku]) ? $newSku[$sku] : $oldSku[$sku];
        $productIds[] = $productData[$this->getProductEntityLinkField()];
        $this->_productSuperAttrs = [];
        $this->_skuSuperData = [];
        if (!empty($productIds)) {
            $mainTable = $this->_resource->getTableName('catalog_product_super_attribute');
            $optionTable = $this->_resource->getTableName('eav_attribute_option');
            $select = $this->connection->select()->from(
                ['m' => $mainTable],
                ['product_id', 'attribute_id', 'product_super_attribute_id']
            )->joinLeft(
                ['o' => $optionTable],
                $this->connection->quoteIdentifier(
                    'm.attribute_id'
                ) . ' = ' . $this->connection->quoteIdentifier(
                    'o.attribute_id'
                ),
                ['option_id']
            )->where(
                'm.product_id IN ( ? )',
                $productIds
            );

            foreach ($this->connection->fetchAll($select) as $row) {
                $attrId = $row['attribute_id'];
                $productId = $row['product_id'];
                if ($row['option_id']) {
                    $this->_skuSuperData[$productId][$attrId][$row['option_id']] = true;
                }
                $this->_productSuperAttrs["{$productId}_{$attrId}"] = $row['product_super_attribute_id'];
            }
        }
        return $this;
    }

    /**
     * Array of SKU to array of super attribute values for all products.
     *
     * @param array $bunch - portion of products to process
     * @param array $newSku - imported variations list
     * @param array $oldSku - present variations list
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function _loadSkuSuperAttributeValuesByRow($rowData, $newSku, $oldSku)
    {
        if ($this->_superAttributes) {
            $attrSetIdToName = $this->_entityModel->getAttrSetIdToName();

            $productIds = [];

            $dataWithExtraVirtualRows = $this->_parseVariations($rowData);

            if (!empty($dataWithExtraVirtualRows)) {
                array_unshift($dataWithExtraVirtualRows, $rowData);
            } else {
                $dataWithExtraVirtualRows[] = $rowData;
            }

            foreach ($dataWithExtraVirtualRows as $data) {
                if (!empty($data['_super_products_sku'])) {
                    if (isset($newSku[$data['_super_products_sku']])) {
                        $productIds[] = $newSku[$data['_super_products_sku']][$this->getProductEntityLinkField()];
                    } elseif (isset($oldSku[$data['_super_products_sku']])) {
                        $productIds[] = $oldSku[$data['_super_products_sku']][$this->getProductEntityLinkField()];
                    }
                }
            }


            foreach ($this->_productColFac->create()->addFieldToFilter(
                'type_id',
                $this->_productTypesConfig->getComposableTypes()
            )->addFieldToFilter(
                $this->getProductEntityLinkField(),
                ['in' => $productIds]
            )->addAttributeToSelect(
                array_keys($this->_superAttributes)
            ) as $product) {
                $attrSetName = $attrSetIdToName[$product->getAttributeSetId()];

                $data = array_intersect_key($product->getData(), $this->_superAttributes);
                foreach ($data as $attrCode => $value) {
                    $attrId = $this->_superAttributes[$attrCode]['id'];
                    $productId = $product->getData($this->getProductEntityLinkField());
                    $this->_skuSuperAttributeValues[$attrSetName][$productId][$attrId] = $value;
                }
            }
        }
        return $this;
    }
    
    /**
     * Parse variations string to inner format.
     *
     * @param array $rowData
     *
     * @return array
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function _parseVariations($rowData){
        $variationsString = empty($rowData['configurable_variations']) ? '' : $rowData['configurable_variations'];
        /*Get variations from additional category columns*/
        $variationKey = 'variation';
        $i = 0;
        while(true){
            $i ++;
            $column = $variationKey.$i;
            if (empty($rowData[$column."_sku"])) break;
            $sku = $rowData[$column."_sku"];
            $variantionData = ['sku'.ImportProduct::PAIR_NAME_VALUE_SEPARATOR.$sku];
            $j = 0;
            while(true){
                $j ++;
                if (
                    empty($rowData[$column."_attribute_code".$j]) ||
                    empty($rowData[$column."_attribute_option".$j])
                ) {
                    break;
                }
                $variantionData[] = $rowData[$column."_attribute_code".$j].ImportProduct::PAIR_NAME_VALUE_SEPARATOR.$rowData[$column."_attribute_option".$j];
            }
            if(sizeof($variantionData) <= 1) continue; /*If there is no attributes data defined just continue to next variation*/
            if($variationsString) $variationsString.= ImportProduct::PSEUDO_MULTI_LINE_SEPARATOR;
            $variationsString.= implode($this->_entityModel->getMultipleValueSeparator(), $variantionData);
        }
        $rowData['configurable_variations'] = $variationsString;
        return parent::_parseVariations($rowData);
    }


}
