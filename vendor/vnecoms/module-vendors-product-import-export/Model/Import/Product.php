<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

// @codingStandardsIgnoreFile

namespace Vnecoms\VendorsProductImportExport\Model\Import;

use Magento\CatalogImportExport\Model\Import\Product\StatusProcessor;
use Magento\CatalogImportExport\Model\Import\Product\StockProcessor;
use Magento\CatalogImportExport\Model\StockItemImporterInterface;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Intl\DateTimeFactory;
use Vnecoms\VendorsProductImportExport\Model\Import as Import;
use Magento\CatalogImportExport\Model\Import\Product\RowValidatorInterface as ValidatorInterface;
use Vnecoms\VendorsProductImportExport\Model\ResourceModel\Import\Data\Collection as ImportSource;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\Stdlib\DateTime;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingError;
use Magento\ImportExport\Model\Import\Entity\AbstractEntity;
use Magento\Catalog\Model\Config as CatalogConfig;
use Magento\Store\Model\Store;
use Vnecoms\VendorsProduct\Model\Source\Approval;
use Magento\Framework\App\ObjectManager;

class Product extends \Magento\CatalogImportExport\Model\Import\Product
{
    const COL_ATTR_SET = 'attribute_set_code';
    /**
     * Codes of attributes which are displayed as dates
     *
     * @var array
     */
    protected $dateAttrCodes = [
        'special_from_date',
        'special_to_date',
        'news_from_date',
        'news_to_date',
        'custom_design_from',
        'custom_design_to'
    ];

    protected $encodeUtf8Field = [
        ""
    ];

    /**
     * @var \Vnecoms\Vendors\Model\Vendor
     */
    protected $_vendor;

    /**
     * Existing vendor products SKU-related information in form of array:
     *
     * [SKU] => array(
     *     'type_id'        => (string) product type
     *     'attr_set_id'    => (int) product attribute set ID
     *     'entity_id'      => (int) product ID
     * )
     *
     * @var array
     */
    protected $_vendorOldSkus = [];

    /**
     * Set Vendor
     *
     * @param \Vnecoms\Vendors\Model\Vendor $vendor
     */
    public function setVendor(\Vnecoms\Vendors\Model\Vendor $vendor){
        $this->_vendor = $vendor;
        return $this;
    }

    /**
     * Get Vendor
     *
     * @return \Vnecoms\Vendors\Model\Vendor
     */
    public function getVendor(){
        return $this->_vendor;
    }

    /**
     * @return \Vnecoms\Vendors\Model\Vendor
     */
    public function getLogger(){
        $om = \Magento\Framework\App\ObjectManager::getInstance();
        return  $om->create('\Psr\Log\LoggerInterface');
    }

    /**
     * @return mixed
     */
    public function getImportHelper(){
        $om = \Magento\Framework\App\ObjectManager::getInstance();
        return  $om->create('\Vnecoms\VendorsProductImportExport\Helper\Data');
    }

    /**
     * Init vendor skus
     *
     * @return \Vnecoms\VendorsProductImportExport\Model\Import\Product
     */
    protected function _initVendorSkus()
    {
        if(!$this->_vendorOldSkus){
            $this->_vendorOldSkus = [];
            $om = \Magento\Framework\App\ObjectManager::getInstance();
            $collection = $om->create('Magento\Catalog\Model\ResourceModel\Product\Collection')
                ->addFieldToFilter('vendor_id', $this->getVendor()->getId())
                ->addAttributeToSelect('approval');

            foreach($collection as $product){
                $this->_vendorOldSkus[$product->getSku()] = [
                    'type_id'       => $product->getTypeId(),
                    'attr_set_id'   => $product->getAttributeSetId(),
                    'entity_id'     => $product->getId(),
                    'approval'      => $product->getApproval(),
                ];
            }
        }
        return $this;
    }

    /**
     * Update old vendor sku
     */
    protected function updateOldVendorSku()
    {
        $this->_vendorOldSkus =[];
        $this->_initVendorSkus();
    }

    /**
     * Validate data rows and save bunches to DB
     *
     * @return $this
     */
    protected function _saveValidatedBunches()
    {
        $source = $this->_getSource();
        $source->rewind();
        while ($source->valid()) {
            try {
                $rowData = $source->current();
            } catch (\InvalidArgumentException $e) {
                $this->addRowError($e->getMessage(), $this->_processedRowsCount);
                $this->_processedRowsCount++;
                $source->next();
                continue;
            }

            $rowData = $this->_customFieldsMapping($rowData);

            $this->validateRow($rowData, $source->key());
            $source->next();
        }
        $this->checkUrlKeyDuplicates();
        $this->getOptionEntity()->validateAmbiguousData();
        return $this->_customSaveValidateBunches();
    }


    public function validateRow(array $rowData, $rowNum){

        $this->_initVendorSkus();

        // BEHAVIOR_DELETE and BEHAVIOR_REPLACE use specific validation logic
        if (Import::BEHAVIOR_REPLACE == $this->getBehavior()) {
            if (!isset($this->_vendorOldSkus[$rowData[self::COL_SKU]])) {
                $this->addRowError(ValidatorInterface::ERROR_SKU_NOT_FOUND_FOR_DELETE, $rowNum);
                return false;
            }
        }
        if (Import::BEHAVIOR_DELETE == $this->getBehavior()) {
            if (!isset($this->_vendorOldSkus[$rowData[self::COL_SKU]])) {
                $this->addRowError(ValidatorInterface::ERROR_SKU_NOT_FOUND_FOR_DELETE, $rowNum);
                return false;
            }
            return true;
        }

        if (Import::BEHAVIOR_APPEND == $this->getBehavior()) {
            if (!isset($this->_vendorOldSkus[$rowData[self::COL_SKU]]) && $this->isSkuExist($rowData[self::COL_SKU])) {
                $this->addRowError(ValidatorInterface::ERROR_SKU_NOT_FOUND_FOR_DELETE, $rowNum,self::COL_SKU,__('The SKU is already in used by another seller'));
                return false;
            }
        }

        return parent::validateRow($rowData, $rowNum);
    }

    /**
     * Validate data rows and save bunches to DB.
     *
     * @return $this|void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function _customSaveValidateBunches()
    {
        $om = \Magento\Framework\App\ObjectManager::getInstance();
        $helper = $om->create('Vnecoms\VendorsProductImportExport\Helper\Data');
        $source = $this->_getSource();
        $bunchRows = [];
        $startNewBunch = false;
        $nextRowBackup = [];
        $maxDataSize = $this->_resourceHelper->getMaxDataSize();
        $bunchSize = $helper->getImportBunchSize();; /*This depends on "max_allowed_packet" setting of mysqlserver*/
        $saveBunchs = [];

        $source->rewind();

        $dataSourceModel = $om->create('Vnecoms\VendorsProductImportExport\Model\ResourceModel\Import\Data');

        while ($source->valid() || $bunchRows) {
            if ($startNewBunch || !$source->valid()) {
                /*Save bunch rows to queue*/
                /* $dataSourceModel->saveBunch($this->getVendor()->getId(), $this->getBehavior(), $bunchRows); */
                $saveBunchs[] = $bunchRows;
                $bunchRows = $nextRowBackup;
                $startNewBunch = false;
                $nextRowBackup = [];
            }
            if ($source->valid()) {
                try {
                    $rowData = $source->current();
                } catch (\InvalidArgumentException $e) {
                    $this->addRowError($e->getMessage(), $this->_processedRowsCount);
                    $this->_processedRowsCount++;
                    $source->next();
                    continue;
                }
                $rowData = $this->_customFieldsMapping($rowData);
                $this->_processedRowsCount++;

                if ($this->validateRow($rowData, $source->key())) {
                    // add row to bunch for save
                    $rowData = $this->_prepareRowForDb($rowData);
                    $isBunchSizeExceeded = $bunchSize > 0 && count($bunchRows) >= $bunchSize;

                    if ($isBunchSizeExceeded) {
                        $startNewBunch = true;
                        $nextRowBackup = [$source->key() => $rowData];
                    } else {
                        $bunchRows[$source->key()] = $rowData;
                    }
                }
                $source->next();
            }
        }

        /**
         * If no error, add all rows to queue
         */
        if(!$this->getErrorAggregator()->getErrorsCount()){
            foreach($saveBunchs as $bunchRows){
                $dataSourceModel->saveBunch($this->getVendor()->getId(), $this->getBehavior(), $bunchRows);
            }
        }
        return $this;
    }


    /**
     * Run import queue
     *
     * @param ImportSource $source
     */
    public function import(ImportSource $source){
        $messages = $result1 = $result2 = ['success' => [], 'error' => []];
        try{
            $result1 = $this->_deleteVendorProducts($source);
            $result2 = $this->_saveVendorProductsData($source);
            // var_dump($result2);exit;
            /* $result2 = ['success' => [], 'error' => []]; */
        }catch (\Exception $e){
            $messages['error'][] = $e->getMessage();
        }
        if(isset($result1['success']) && is_array($result1['success'])){
            $messages['success'] = array_merge($messages['success'], $result1['success']);
        }
        if(isset($result2['success']) && is_array($result2['success'])){
            $messages['success'] = array_merge($messages['success'], $result2['success']);
        }

        if(isset($result1['error']) && is_array($result1['error'])){
            $messages['error'] = array_merge($messages['error'], $result1['error']);
        }
        if(isset($result2['error']) && is_array($result2['error'])){
            $messages['error'] = array_merge($messages['error'], $result2['error']);
        }
        return $messages;
    }


    /**
     * Delete products.
     *
     * @return $this
     * @throws \Exception
     */
    protected function _deleteVendorProducts(ImportSource $source)
    {
        $productEntityTable = $this->_resourceFactory->create()->getEntityTable();

        $idsToDelete = [];
        $processedIds = [];
        $messages = ['success' => [],'error' =>[]];
        $skusToDelete = [];

        foreach ($source as $rowData) {
            $sku = strtolower($rowData->getSku());
            if($rowData->getBehavior() == \Vnecoms\VendorsProductImportExport\Model\Import::BEHAVIOR_DELETE){
                if($this->isSkuExist($sku)){
                    $idsToDelete[] = $this->getExistingSku($sku)['entity_id'];
                    $processedIds[] = $rowData->getId();
                    $messages['success'][] = __("Product %1 is deleted.", $rowData->getSku());
                    $skusToDelete[] = $rowData->getSku();
                }else{
                    $messages['error'][] = __("Product %1 is not available to delete.", $rowData->getSku());
                    $rowData->setStatus(\Vnecoms\VendorsProductImportExport\Model\Import\Data::STATUS_ERROR)
                        ->setErrorMsg(__('The product is not available.'))->save();
                }
            }
        }

        if ($idsToDelete) {
            $this->transactionManager->start($this->_connection);
            try {
                /*Delete products*/
                $this->objectRelationProcessor->delete(
                    $this->transactionManager,
                    $this->_connection,
                    $productEntityTable,
                    $this->_connection->quoteInto('entity_id IN (?)', $idsToDelete),
                    ['entity_id' => $idsToDelete]
                );

                $this->_eventManager->dispatch(
                    'vendors_catalog_product_import_detete_after',
                    ['skus' => $skusToDelete]
                );

                /*Delete from URL Rewrite*/
                $this->objectRelationProcessor->delete(
                    $this->transactionManager,
                    $this->_connection,
                    $source->getTable('url_rewrite'),
                    [
                        'entity_id IN (?)' => $idsToDelete,
                        'entity_type = ?' => 'product'
                    ],
                    ['entity_id' => $idsToDelete]
                );

                /*Delete processed queue items*/
                $this->objectRelationProcessor->delete(
                    $this->transactionManager,
                    $this->_connection,
                    $source->getTable('ves_vendor_product_import_queue'),
                    $this->_connection->quoteInto('queue_id IN (?)', $processedIds),
                    ['queue_id' => $processedIds]
                );


                $this->transactionManager->commit();
                return $messages;
            } catch (\Exception $e) {
                $this->transactionManager->rollBack();
                return ['success' => [],'error' => [$e->getMessage()]];
            }
        }
        return ['success' => [],'error' => []];
    }


    /**
     * Save products data.
     *
     * @return $this
     */
    protected function _saveVendorProductsData(ImportSource $source)
    {
        $result = $this->_saveVendorProducts($source);
        $this->updateOldVendorSku();
        foreach ($this->_productTypeModels as $productTypeModel) {
            $productTypeModel->setSource($source)
                ->saveData();
        }

        /* Save related, crossell, upsell products*/
        $this->_saveVendorProductLinks($source);

        /*Save product stocks*/
        $this->_saveVendorStockItem($source);

        /* $this->getOptionEntity()->importData(); */

        return $result;
    }


    /**
     * Get existing images for current bucnh
     *
     * @param ImportSource $source
     * @return array
     */
    protected function _getExistingImages(ImportSource $source)
    {
        $result = [];
        if ($this->getErrorAggregator()->hasToBeTerminated()) {
            return $result;
        }

        $this->initMediaGalleryResources();
        $productSKUs = $source->getColumnValues(self::COL_SKU);
        $select = $this->_connection->select()->from(
            ['mg' => $this->mediaGalleryTableName],
            ['value' => 'mg.value']
        )->joinInner(
            ['mgvte' => $this->mediaGalleryEntityToValueTableName],
            '(mg.value_id = mgvte.value_id)',
            [$this->getProductEntityLinkField() => 'mgvte.' . $this->getProductEntityLinkField()]
        )->joinInner(
            ['pe' => $this->productEntityTableName],
            "(mgvte.{$this->getProductEntityLinkField()} = pe.{$this->getProductEntityLinkField()})",
            ['sku' => 'pe.sku']
        )->where(
            'pe.sku IN (?)',
            $productSKUs
        );

        foreach ($this->_connection->fetchAll($select) as $image) {
            $result[$image['sku']][$image['value']] = true;
        }

        return $result;
    }

    /**
     * Whether a url key is needed to be change.
     *
     * @param array $rowData
     * @return bool
     */
    private function isNeedToChangeUrlKey(array $rowData): bool
    {
        $urlKey = $this->getUrlKey($rowData);
        $productExists = $this->isSkuExist($rowData[self::COL_SKU]);
        $markedToEraseUrlKey = isset($rowData[self::URL_KEY]);
        // The product isn't new and the url key index wasn't marked for change.
        if (!$urlKey && $productExists && !$markedToEraseUrlKey) {
            // Seems there is no need to change the url key
            return false;
        }

        return true;
    }

    /**
     * Retrieve url key from provided row data.
     *
     * @param array $rowData
     * @return string
     *
     * @since 100.0.3
     */
    protected function getUrlKey($rowData)
    {
        if (!empty($rowData[self::URL_KEY])) {
            $urlKey = (string) $rowData[self::URL_KEY];
            return $this->productUrl->formatUrlKey($urlKey);
        }

        if (!empty($rowData[self::COL_NAME])
            && (array_key_exists(self::URL_KEY, $rowData) || !$this->isSkuExist($rowData[self::COL_SKU]))) {
            return $this->productUrl->formatUrlKey($rowData[self::COL_NAME]);
        }

        return '';
    }


    /**
     * Gather and save information about product entities.
     *
     * @param ImportSource $source
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    protected function _saveVendorProducts(ImportSource $source)
    {
        $result = ['success' => [], 'error' => []];
        $priceIsGlobal = $this->_catalogData->isPriceGlobal();
        $productLimit = null;
        $productsQty = null;

        $entityRowsIn = [];
        $entityRowsUp = [];
        $attributes = [];
        $this->websitesCache = [];
        $this->categoriesCache = [];
        $tierPrices = [];
        $mediaGallery = [];
        $uploadedImages = [];
        $previousType = null;
        $prevAttributeSet = null;
        $existingImages = $this->_getExistingImages($source);
        $processedQueueIds = [];
        $sourceArr = [];
        $successMessage = [];
        $catalogConfig = \Magento\Framework\App\ObjectManager::getInstance()
            ->get(CatalogConfig::class);
        $bunch = [];

        $utf8Attributes = $this->getImportHelper()->getUtf8Attribute();

        foreach ($source as $rowKey=>$row) {
            $sourceArr[$row->getId()] = $row;
            if($row->getBehavior() != \Vnecoms\VendorsProductImportExport\Model\Import::BEHAVIOR_APPEND) continue;

            $rowData = json_decode($row->getProductData(), true);
            if (!$rowData) continue;
            $rowData[self::COL_SKU] = $row->getSku();

            foreach ($utf8Attributes as $attribute) {
                if (isset($row[$attribute])) {
                    if (mb_detect_encoding($row[$attribute], 'UTF-8') == "UTF-8") {
                        $row[$attribute] = utf8_decode($row[$attribute]);
                    }
                }
            }
            
            $rowNum = $row->getQueueId();
            $bunch[$rowKey] = $rowData;
            if (!$this->validateRow($rowData, $rowNum)) {
                continue;
            }

            $rowData['vendor_id'] = $this->getVendor()->getId();

            $transport = new \Magento\Framework\DataObject(array(
                'adapter'=> $this,
                'row' => $row,
                'row_data'=>$rowData,
                'row_num' => $rowNum,
                'continue_process' => true
            ));

            $this->_eventManager->dispatch(
                'vendors_catalog_product_import_row_before',
                ['transport' => $transport]
            );

            if (!$transport->getContinueProcess()) {
                continue;
            }

            $rowData = $transport->getRowData();
            /* if ($this->getErrorAggregator()->hasToBeTerminated()) {
                $this->getErrorAggregator()->addRowToSkip($rowNum);
                continue;
            } */

            $urlKey = $this->getUrlKey($rowData);
            $bunch[$rowKey][self::URL_KEY] = $rowData[self::URL_KEY] = $urlKey;

            $rowScope = $this->getRowScope($rowData);

            $rowSku = $rowData[self::COL_SKU];

            if (self::SCOPE_STORE == $rowScope) {
                // set necessary data from SCOPE_DEFAULT row
                $rowData[self::COL_TYPE] = $this->skuProcessor->getNewSku($rowSku)['type_id'];
                $rowData['attribute_set_id'] = $this->skuProcessor->getNewSku($rowSku)['attr_set_id'];
                $rowData[self::COL_ATTR_SET] = $this->skuProcessor->getNewSku($rowSku)['attr_set_code'];
            }

            // 1. Entity phase
            if ($this->isSkuExist($rowSku)) {

                if (isset($rowData['attribute_set_code'])) {

                    $attributeSetId = $catalogConfig->getAttributeSetId(
                        $this->getEntityTypeId(),
                        $rowData['attribute_set_code']
                    );

                    // wrong attribute_set_code was received
                    if (!$attributeSetId) {
                        throw new \Magento\Framework\Exception\LocalizedException(
                            __(
                                'Wrong attribute set code "%1", please correct it and try again.',
                                $rowData['attribute_set_code']
                            )
                        );
                    }
                } else {
                    $attributeSetId = $this->skuProcessor->getNewSku($rowSku)['attr_set_id'];
                }


                $entityRowsUp[] = [
                    'updated_at' => (new \DateTime())->format(DateTime::DATETIME_PHP_FORMAT),
                    'attribute_set_id' => $attributeSetId,
                    $this->getProductEntityLinkField() => $this->getExistingSku($rowSku)[$this->getProductEntityLinkField()]
                ];

            } else {
                if (!$productLimit || $productsQty < $productLimit) {
                    $entityRowsIn[$rowSku] = [
                        'attribute_set_id' => $this->skuProcessor->getNewSku($rowSku)['attr_set_id'],
                        'type_id' => $this->skuProcessor->getNewSku($rowSku)['type_id'],
                        'sku' => $rowSku,
                        'vendor_id' => $rowData['vendor_id'], /*Set the vendor id for imported item*/
                        'has_options' => isset($rowData['has_options']) ? $rowData['has_options'] : 0,
                        'created_at' => (new \DateTime())->format(DateTime::DATETIME_PHP_FORMAT),
                        'updated_at' => (new \DateTime())->format(DateTime::DATETIME_PHP_FORMAT),
                    ];
                    $productsQty++;
                } else {
                    $rowSku = null;
                    // sign for child rows to be skipped
                    $this->getErrorAggregator()->addRowToSkip($rowNum);
                    continue;
                }
            }

            if (!array_key_exists($rowSku, $this->websitesCache)) {
                $this->websitesCache[$rowSku] = [];
            }
            // 2. Product-to-Website phase
            if (!empty($rowData[self::COL_PRODUCT_WEBSITES])) {
                $websiteCodes = explode($this->getMultipleValueSeparator(), $rowData[self::COL_PRODUCT_WEBSITES]);
                foreach ($websiteCodes as $websiteCode) {
                    $websiteId = $this->storeResolver->getWebsiteCodeToId($websiteCode);
                    $this->websitesCache[$rowSku][$websiteId] = true;
                }
            }

            // 3. Categories phase
            if (!array_key_exists($rowSku, $this->categoriesCache)) {
                $this->categoriesCache[$rowSku] = [];
            }
            $rowData['rowNum'] = $rowNum;

            /*-------------------------- don't add product if the category is not available-----------------------------------*/
            $categoryIds = $this->processRowCategories($rowData);
            /*------------------------------------------------------------------*/

            foreach ($categoryIds as $id) {
                $this->categoriesCache[$rowSku][$id] = true;
            }
            unset($rowData['rowNum']);

            // 4.1. Tier prices phase
            if (!empty($rowData['_tier_price_website'])) {
                $tierPrices[$rowSku][] = [
                    'all_groups' => $rowData['_tier_price_customer_group'] == self::VALUE_ALL,
                    'customer_group_id' => $rowData['_tier_price_customer_group'] ==
                    self::VALUE_ALL ? 0 : $rowData['_tier_price_customer_group'],
                    'qty' => $rowData['_tier_price_qty'],
                    'value' => $rowData['_tier_price_price'],
                    'website_id' => self::VALUE_ALL == $rowData['_tier_price_website'] ||
                    $priceIsGlobal ? 0 : $this->storeResolver->getWebsiteCodeToId($rowData['_tier_price_website']),
                ];
            }

            if (!$this->validateRow($rowData, $rowNum)) {
                continue;
            }
            $processedQueueIds[$row->getQueueId()] = $row->getQueueId();

            // 5. Media gallery phase
            list($rowImages, $rowLabels) = $this->getImagesFromRow($rowData);
            $storeId = !empty($rowData[self::COL_STORE])
                ? $this->getStoreIdByCode($rowData[self::COL_STORE])
                : Store::DEFAULT_STORE_ID;
            $imageHiddenStates = $this->getImagesHiddenStates($rowData);
            foreach (array_keys($imageHiddenStates) as $image) {
                if (array_key_exists($rowSku, $existingImages)
                    && array_key_exists($image, $existingImages[$rowSku])
                ) {
                    $rowImages[self::COL_MEDIA_IMAGE][] = $image;
                    $uploadedImages[$image] = $image;
                }

                if (empty($rowImages)) {
                    $rowImages[self::COL_MEDIA_IMAGE][] = $image;
                }
            }

            $rowData[self::COL_MEDIA_IMAGE] = [];

            /*
             * Note: to avoid problems with undefined sorting, the value of media gallery items positions
             * must be unique in scope of one product.
             */
            $position = 0;
            foreach ($rowImages as $column => $columnImages) {
                foreach ($columnImages as $columnImageKey => $columnImage) {
                    if (!isset($uploadedImages[$columnImage])) {
                        $uploadedFile = $this->uploadMediaFiles($columnImage);
                        $uploadedFile = $uploadedFile ?: $this->getSystemFile($columnImage);
                        if ($uploadedFile) {
                            $uploadedImages[$columnImage] = $uploadedFile;
                        } else {
                            $this->addRowError(
                                ValidatorInterface::ERROR_MEDIA_URL_NOT_ACCESSIBLE,
                                $rowNum,
                                null,
                                null,
                                ProcessingError::ERROR_LEVEL_NOT_CRITICAL
                            );
                        }
                    } else {
                        $uploadedFile = $uploadedImages[$columnImage];
                    }

                    if ($uploadedFile && $column !== self::COL_MEDIA_IMAGE) {
                        $rowData[$column] = $uploadedFile;
                    }

                    if ($uploadedFile && !isset($mediaGallery[$storeId][$rowSku][$uploadedFile])) {
                        if (isset($existingImages[$rowSku][$uploadedFile])) {
                            $currentFileData = $existingImages[$rowSku][$uploadedFile];
                            if (is_array($currentFileData)) {
                                if (isset($rowLabels[$column][$columnImageKey]) &&
                                    isset($currentFileData['label'])
                                    && $rowLabels[$column][$columnImageKey] !=
                                    $currentFileData['label']
                                ) {
                                    $labelsForUpdate[] = [
                                        'label' => $rowLabels[$column][$columnImageKey],
                                        'imageData' => $currentFileData
                                    ];
                                }

                                if (array_key_exists($uploadedFile, $imageHiddenStates)
                                    && $currentFileData['disabled'] != $imageHiddenStates[$uploadedFile]
                                ) {
                                    $imagesForChangeVisibility[] = [
                                        'disabled' => $imageHiddenStates[$uploadedFile],
                                        'imageData' => $currentFileData
                                    ];
                                }
                            }
                        } else {
                            if ($column == self::COL_MEDIA_IMAGE) {
                                $rowData[$column][] = $uploadedFile;
                            }
                            $mediaGallery[$storeId][$rowSku][$uploadedFile] = [
                                'attribute_id' => $this->getMediaGalleryAttributeId(),
                                'label' => isset($rowLabels[$column][$columnImageKey])
                                    ? $rowLabels[$column][$columnImageKey]
                                    : '',
                                'position' => ++$position,
                                'disabled' => isset($imageHiddenStates[$columnImage])
                                    ? $imageHiddenStates[$columnImage] : '0',
                                'value' => $uploadedFile,
                            ];
                        }
                    }
                }
            }


            // 6. Attributes phase
            $rowStore = (self::SCOPE_STORE == $rowScope)
                ? $this->storeResolver->getStoreCodeToId($rowData[self::COL_STORE])
                : 0;
            $productType = isset($rowData[self::COL_TYPE]) ? $rowData[self::COL_TYPE] : null;
            if (!is_null($productType)) {
                $previousType = $productType;
            }
            if (isset($rowData[self::COL_ATTR_SET])) {
                $prevAttributeSet = $rowData[self::COL_ATTR_SET];
            }
            if (self::SCOPE_NULL == $rowScope) {
                // for multiselect attributes only
                if (!is_null($prevAttributeSet)) {
                    $rowData[self::COL_ATTR_SET] = $prevAttributeSet;
                }
                if (is_null($productType) && !is_null($previousType)) {
                    $productType = $previousType;
                }
                if (is_null($productType)) {
                    continue;
                }
            }

            $productTypeModel = $this->_productTypeModels[$productType];
            if (!empty($rowData['tax_class_name'])) {
                $rowData['tax_class_id'] =
                    $this->taxClassProcessor->upsertTaxClass($rowData['tax_class_name'], $productTypeModel);
            }

            if (empty($rowData[self::COL_SKU])) {
                $rowData = $productTypeModel->clearEmptyData($rowData);
            }

            $rowData = $productTypeModel->prepareAttributesWithDefaultValueForSave(
                $rowData,
                !$this->isSkuExist($rowSku)
            );
            $product = $this->_proxyProdFactory->create(['data' => $rowData]);

            $om = \Magento\Framework\App\ObjectManager::getInstance();
            /** @var \Vnecoms\VendorsProduct\Helper\Data*/
            $helper = $om->get('Vnecoms\VendorsProduct\Helper\Data');
            /* Process product update*/
            if($this->isSkuExist($rowSku)){
                if($helper->isUpdateProductsApproval()){
                    if(!in_array(
                        $this->_vendorOldSkus[$rowSku]['approval'],
                        [Approval::STATUS_NOT_SUBMITED, Approval::STATUS_PENDING, Approval::STATUS_UNAPPROVED]
                    )){
                        $rowData = $this->processProductUdate($rowSku, $rowData);
                        $rowData['approval'] = Approval::STATUS_PENDING_UPDATE;
                    }
                }else{
                    $rowData['approval'] = Approval::STATUS_APPROVED;
                }
            }else{
                $rowData['approval'] = $helper->isNewProductsApproval()?
                    Approval::STATUS_PENDING:
                    Approval::STATUS_APPROVED;
            }

            foreach ($rowData as $attrCode => $attrValue) {
                $attribute = $this->retrieveAttributeByCode($attrCode);

                if ('multiselect' != $attribute->getFrontendInput() && self::SCOPE_NULL == $rowScope) {
                    // skip attribute processing for SCOPE_NULL rows
                    continue;
                }
                $attrId = $attribute->getId();
                $backModel = $attribute->getBackendModel();
                $attrTable = $attribute->getBackend()->getTable();
                $storeIds = [0];

                if (
                    'datetime' == $attribute->getBackendType()
                    && (
                        in_array($attribute->getAttributeCode(), $this->dateAttrCodes)
                        || $attribute->getIsUserDefined()
                    )
                ) {
                    $attrValue = $this->dateTime->formatDate($attrValue, false);
                } else if ('datetime' == $attribute->getBackendType() && strtotime($attrValue)) {
                    $attrValue = $this->dateTime->gmDate(
                        'Y-m-d H:i:s',
                        $this->_localeDate->date($attrValue)->getTimestamp()
                    );
                } elseif ($backModel) {
                    $attribute->getBackend()->beforeSave($product);
                    $attrValue = $product->getData($attribute->getAttributeCode());
                }
                if (self::SCOPE_STORE == $rowScope) {
                    if (self::SCOPE_WEBSITE == $attribute->getIsGlobal()) {
                        // check website defaults already set
                        if (!isset($attributes[$attrTable][$rowSku][$attrId][$rowStore])) {
                            $storeIds = $this->storeResolver->getStoreIdToWebsiteStoreIds($rowStore);
                        }
                    } elseif (self::SCOPE_STORE == $attribute->getIsGlobal()) {
                        $storeIds = [$rowStore];
                    }
                    if (!$this->isSkuExist($rowSku)) {
                        $storeIds[] = 0;
                    }
                }
                foreach ($storeIds as $storeId) {
                    if (!isset($attributes[$attrTable][$rowSku][$attrId][$storeId])) {
                        $attributes[$attrTable][$rowSku][$attrId][$storeId] = $attrValue;
                    }
                }
                // restore 'backend_model' to avoid 'default' setting
                $attribute->setBackendModel($backModel);
            }

            $this->_eventManager->dispatch(
                'vendors_catalog_product_import_row_after',
                [
                    'adapter' => $this,
                    'row' => $row,
                    'rowData' => $rowData,
                    'product' => $product,
                    'is_update' => $this->isSkuExist($rowSku)
                ]
            );

            $successMessage[$row->getId()] = __('Item "%1" has been imported.', $row->getSku());


        }

        $errors = [];
        foreach($this->getErrorAggregator()->getAllErrors() as $error){
            $errors[$error->getRowNumber()][] = $error->getErrorMessage();
            $result['error'][] = __('Item "%1": %2',$sourceArr[$error->getRowNumber()]->getSku(),$error->getErrorMessage());
        }

        foreach($errors as $rowId=>$errs){
            unset($processedQueueIds[$rowId]);
            unset($successMessage[$rowId]);
            $sourceArr[$rowId]->setStatus(\Vnecoms\VendorsProductImportExport\Model\Import\Data::STATUS_ERROR)
                ->setErrorMsg(json_encode($errs))->save();
        }


        $this->transactionManager->start($this->_connection);
        try {
            $this->saveProductEntity(
                $entityRowsIn,
                $entityRowsUp
            )->_saveProductWebsites(
                $this->websitesCache
            )->_saveProductCategories(
                $this->categoriesCache
            )->_saveProductTierPrices(
                $tierPrices
            )->_saveMediaGallery(
                $mediaGallery
            )->_saveProductAttributes(
                $attributes
            );
            /*Delete processed queue items*/
            $this->objectRelationProcessor->delete(
                $this->transactionManager,
                $this->_connection,
                $source->getTable('ves_vendor_product_import_queue'),
                $this->_connection->quoteInto('queue_id IN (?)', $processedQueueIds),
                ['queue_id' => $processedQueueIds]
            );

            $this->transactionManager->commit();

            $result['success'] = array_merge($result['success'],array_values($successMessage));

            $this->_eventManager->dispatch(
                'catalog_product_import_bunch_save_after',
                ['adapter' => $this, 'bunch' => $bunch]
            );
        } catch (\Exception $e) {
            $this->transactionManager->rollBack();
            return ['success' => [],'error' => [$e->getMessage()]];
        }

        return $result;
    }

    /**
     * Try to find file by it's path.
     *
     * @param string $fileName
     * @return string
     */
    private function getSystemFile($fileName)
    {
        $filePath = 'catalog' . DIRECTORY_SEPARATOR . 'product' . DIRECTORY_SEPARATOR . $fileName;
        $fileSystem = ObjectManager::getInstance()->create('Magento\Framework\Filesystem');
        /** @var \Magento\Framework\Filesystem\Directory\ReadInterface $read */
        $read = $fileSystem->getDirectoryRead(DirectoryList::MEDIA);

        return $read->isExist($filePath) && $read->isReadable($filePath) ? $fileName : '';
    }

    /**
     * @see \Magento\CatalogImportExport\Model\Import\Product::getImagesFromRow($rowData)
     */
    public function getImagesFromRow(array $rowData){
        $additionalImageKey = 'additional_image';
        list($rowImages, $rowLabels) = parent::getImagesFromRow($rowData);
        $i = 0;
        while(true){
            $i ++;
            $column = $additionalImageKey."_".$i;
            if (empty($rowData[$column])) break;
            $image = trim($rowData[$column]);
            if(isset($rowImages[self::COL_MEDIA_IMAGE]) && in_array($image, $rowImages[self::COL_MEDIA_IMAGE])) continue;
            if(!isset($rowImages[self::COL_MEDIA_IMAGE])){
                $rowImages[self::COL_MEDIA_IMAGE] = [];
            }
            $rowImages[self::COL_MEDIA_IMAGE][] = $image;

            if(empty($rowData[$column.'_label'])) continue;

            if(!isset($rowLabels[self::COL_MEDIA_IMAGE])){
                $rowLabels[self::COL_MEDIA_IMAGE] = [];
            }
            $rowLabels[self::COL_MEDIA_IMAGE][count($rowImages[self::COL_MEDIA_IMAGE])-1] = $rowData[$column.'_label'];
        }

        return [$rowImages, $rowLabels];
    }

    /**
     * Process Product Update
     * @param array $rowData
     * @return array
     */
    protected function processProductUdate($rowSku, $rowData){
        $om = \Magento\Framework\App\ObjectManager::getInstance();
        /** @var \Vnecoms\VendorsProduct\Helper\Data*/
        $helper = $om->get('Vnecoms\VendorsProduct\Helper\Data');
        $selectedAtributes = $helper->getUpdateProductsApprovalAttributes();
        $changedData = [];
        if ($helper->getUpdateProductsApprovalFlag()) {
            /*All selected attributes will be required for approval*/
            foreach($selectedAtributes as $attr){
                if(!isset($rowData[$attr])) continue;
                $changedData[$attr] = $rowData[$attr];
                unset($rowData[$attr]);
            }
        }else{
            /*All selected attributes will be NOT required for approval*/
            foreach($rowData as $attr => $value){
                if(in_array($attr, $selectedAtributes)) continue;
                $changedData[$attr] = $rowData[$attr];
                unset($rowData[$attr]);
            }
        }
        $productId = $this->getExistingSku($rowSku)['entity_id'];
        /** @var \Vnecoms\VendorsProduct\Model\Product\Update*/
        $update = $om->create('Vnecoms\VendorsProduct\Model\Product\Update');

        /*Delete exist updates*/
        $resource = $update->getResource();
        $resource->getConnection()->delete(
            $resource->getTable('ves_vendor_product_update'),
            ['product_id = ?' => $productId]
        );

        $storeId = !empty($rowData[self::COL_STORE])
            ? $this->getStoreIdByCode($rowData[self::COL_STORE])
            : Store::DEFAULT_STORE_ID;

        /*Update only allowed attributes*/
        /*Unset approval attribute from row data so not require approval attribute can still be updated*/
        $update->setData([
            'vendor_id' => $this->getVendor()->getId(),
            'store_id' => $storeId,
            'product_id' => $productId,
            'product_data' => serialize($changedData),
            'status' => \Vnecoms\VendorsProduct\Model\Product\Update::STATUS_PENDING
        ])->save();

        $rowData['approval'] = \Vnecoms\VendorsProduct\Model\Source\Approval::STATUS_PENDING_UPDATE;
        return $rowData;
    }

    /**
     * Prepare array with image states (visible or hidden from product page)
     *
     * @param array $rowData
     * @return array
     */
    private function getImagesHiddenStates($rowData)
    {
        $statesArray = [];
        $mappingArray = [
            '_media_is_disabled' => '1'
        ];

        foreach ($mappingArray as $key => $value) {
            if (isset($rowData[$key]) && strlen(trim($rowData[$key]))) {
                $items = explode($this->getMultipleValueSeparator(), $rowData[$key]);

                foreach ($items as $item) {
                    $statesArray[$item] = $value;
                }
            }
        }

        return $statesArray;
    }


    /**
     * Gather and save information about product links.
     * Must be called after ALL products saving done.
     * @param ImportSource $source
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _saveVendorProductLinks(ImportSource $source)
    {
        $resource = $this->_linkFactory->create();
        $mainTable = $resource->getMainTable();
        $positionAttrId = [];
        $nextLinkId = $this->_resourceHelper->getNextAutoincrement($mainTable);

        // pre-load 'position' attributes ID for each link type once
        foreach ($this->_linkNameToId as $linkName => $linkId) {
            $select = $this->_connection->select()->from(
                $resource->getTable('catalog_product_link_attribute'),
                ['id' => 'product_link_attribute_id']
            )->where(
                'link_type_id = :link_id AND product_link_attribute_code = :position'
            );
            $bind = [':link_id' => $linkId, ':position' => 'position'];
            $positionAttrId[$linkId] = $this->_connection->fetchOne($select, $bind);
        }

        $productIds = [];
        $deleteProductIds = [];

        $linkRows = [];
        $positionRows = [];

        foreach ($source as $row) {
            $rowData = json_decode($row->getProductData(), true);
            $rowData[self::COL_SKU] = $row->getSku();
            if (!$this->isRowAllowedToImport($rowData, $row->getId())) {
                continue;
            }

            $sku = $rowData[self::COL_SKU];

            $productId = $this->skuProcessor->getNewSku($sku)[$this->getProductEntityLinkField()];
            $productLinkKeys = [];
            $select = $this->_connection->select()->from(
                $resource->getTable('catalog_product_link'),
                ['id' => 'link_id', 'linked_id' => 'linked_product_id', 'link_type_id' => 'link_type_id']
            )->where(
                'product_id = :product_id'
            );
            $bind = [':product_id' => $productId];
            foreach ($this->_connection->fetchAll($select, $bind) as $linkData) {
                $linkKey = "{$productId}-{$linkData['linked_id']}-{$linkData['link_type_id']}";
                $productLinkKeys[$linkKey] = $linkData['id'];
            }

            if($row->getBehavior() == \Vnecoms\VendorsProductImportExport\Model\Import::BEHAVIOR_DELETE){
                $deleteProductIds[] = $productId;
            }

            foreach ($this->_linkNameToId as $linkName => $linkId) {
                $productIds[] = $productId;
                if (isset($rowData[$linkName . 'sku'])) {
                    $linkSkus = explode($this->getMultipleValueSeparator(), $rowData[$linkName . 'sku']);
                    $linkPositions = !empty($rowData[$linkName . 'position'])
                        ? explode($this->getMultipleValueSeparator(), $rowData[$linkName . 'position'])
                        : [];
                    foreach ($linkSkus as $linkedKey => $linkedSku) {
                        $linkedSku = trim($linkedSku);
                        if ((!is_null($this->skuProcessor->getNewSku($linkedSku)) ||
                                $this->isSkuExist($linkedSku))
                            && $linkedSku != $sku
                        ) {
                            $newSku = $this->skuProcessor->getNewSku($linkedSku);
                            if (!empty($newSku)) {
                                $linkedId = $newSku['entity_id'];
                            } else {
                                $linkedId = $this->getExistingSku($linkedSku)['entity_id'];
                            }

                            if ($linkedId == null) {
                                // Import file links to a SKU which is skipped for some reason,
                                // which leads to a "NULL"
                                // link causing fatal errors.
                                $this->getLogger()->critical(
                                    new \Exception(
                                        sprintf(
                                            'WARNING: Orphaned link skipped: From SKU %s (ID %d) to SKU %s, ' .
                                            'Link type id: %d',
                                            $sku,
                                            $productId,
                                            $linkedSku,
                                            $linkId
                                        )
                                    )
                                );
                                continue;
                            }

                            $linkKey = "{$productId}-{$linkedId}-{$linkId}";
                            if(empty($productLinkKeys[$linkKey])) {
                                $productLinkKeys[$linkKey] = $nextLinkId;
                            }
                            if (!isset($linkRows[$linkKey])) {
                                $linkRows[$linkKey] = [
                                    'link_id' => $productLinkKeys[$linkKey],
                                    'product_id' => $productId,
                                    'linked_product_id' => $linkedId,
                                    'link_type_id' => $linkId,
                                ];
                                if (!empty($linkPositions[$linkedKey])) {
                                    $positionRows[] = [
                                        'link_id' => $productLinkKeys[$linkKey],
                                        'product_link_attribute_id' => $positionAttrId[$linkId],
                                        'value' => $linkPositions[$linkedKey],
                                    ];
                                }
                                $nextLinkId++;
                            }
                        }
                    }
                }
            }
        }
        if ($deleteProductIds) {
            $this->_connection->delete(
                $mainTable,
                $this->_connection->quoteInto('product_id IN (?)', array_unique($deleteProductIds))
            );
        }

        if ($linkRows) {
            $this->_connection->insertOnDuplicate($mainTable, $linkRows, ['link_id']);
        }
        if ($positionRows) {
            // process linked product positions
            $this->_connection->insertOnDuplicate(
                $resource->getAttributeTypeTable('int'),
                $positionRows, ['value']
            );
        }

        return $this;
    }

    /**
     * Stock item saving.
     *
     * @return $this
     */
    protected function _saveVendorStockItem(ImportSource $source)
    {
        $stockData = [];
        $productIdsToReindex = [];
        $stockChangedProductIds = [];

        foreach ($source as $rowData) {
            $rowNum = $rowData->getQueueId();
            $sku = $rowData->getSku();
            $rowData = json_decode($rowData->getData('product_data'), true);
            $rowData[self::COL_SKU] = $sku;
            if (!$this->isRowAllowedToImport($rowData, $rowNum)) {
                continue;
            }

            $row = [];
            $sku = $rowData[self::COL_SKU];
            if ($this->skuProcessor->getNewSku($sku) !== null) {
                $stockItem = $this->getRowExistingStockItem($rowData);
                $existingStockItemData = $stockItem->getData();
                $row = $this->formatStockDataForRow($rowData);
                $productIdsToReindex[] = $row['product_id'];
                $storeId = $this->getRowStoreId($rowData);
                $statusProcessor = ObjectManager::getInstance()
                    ->get(StatusProcessor::class);
                if (!empty(array_diff_assoc($row, $existingStockItemData))
                    || $statusProcessor->isStatusChanged($sku, $storeId)
                ) {
                    $stockChangedProductIds[] = $row['product_id'];
                }
            }

            if (!isset($stockData[$sku])) {
                $stockData[$sku] = $row;
            }
        }

        // Insert rows
        if (!empty($stockData)) {
            $stockItemImporter = ObjectManager::getInstance()
                ->create(StockItemImporterInterface::class);
            $stockItemImporter->import($stockData);
        }

        $this->reindexStockStatus($stockChangedProductIds);
        $this->reindexProducts($productIdsToReindex);

        return $this;
    }



    /**
     * Initiate product reindex by product ids
     *
     * @param array $productIdsToReindex
     * @return void
     */
    private function reindexProducts($productIdsToReindex = [])
    {
        $indexer = $this->indexerRegistry->get('catalog_product_category');
        if (is_array($productIdsToReindex) && count($productIdsToReindex) > 0 && !$indexer->isScheduled()) {
            $indexer->reindexList($productIdsToReindex);
        }
    }

    /**
     * Reindex stock status for provided product IDs
     *
     * @param array $productIds
     */
    private function reindexStockStatus(array $productIds): void
    {
        if ($productIds) {
            $stockProcessor = ObjectManager::getInstance()
                ->get(StockProcessor::class);
            $stockProcessor->reindexList($productIds);
        }
    }

    /**
     * Get row store ID
     *
     * @param array $rowData
     * @return int
     */
    private function getRowStoreId(array $rowData): int
    {
        return !empty($rowData[self::COL_STORE])
            ? (int) $this->getStoreIdByCode($rowData[self::COL_STORE])
            : Store::DEFAULT_STORE_ID;
    }

    /**
     * Format row data to DB compatible values.
     *
     * @param array $rowData
     * @return array
     */
    private function formatStockDataForRow(array $rowData): array
    {
        $sku = $rowData[self::COL_SKU];
        $row['product_id'] = $this->skuProcessor->getNewSku($sku)['entity_id'];
        $row['website_id'] = $this->stockConfiguration->getDefaultScopeId();
        $row['stock_id'] = $this->stockRegistry->getStock($row['website_id'])->getStockId();

        $stockItemDo = $this->stockRegistry->getStockItem($row['product_id'], $row['website_id']);
        $existStockData = $stockItemDo->getData();

        if (isset($rowData['qty']) && $rowData['qty'] == 0 && !isset($rowData['is_in_stock'])) {
            $rowData['is_in_stock'] = 0;
        }

        $row = array_merge(
            $this->defaultStockData,
            array_intersect_key($existStockData, $this->defaultStockData),
            array_intersect_key($rowData, $this->defaultStockData),
            $row
        );

        if ($this->stockConfiguration->isQty($this->skuProcessor->getNewSku($sku)['type_id'])) {
            $stockItemDo->setData($row);
            $row['is_in_stock'] = $row['is_in_stock'] ?? $this->stockStateProvider->verifyStock($stockItemDo);
            if ($this->stockStateProvider->verifyNotification($stockItemDo)) {
                $dateTimeFactory = ObjectManager::getInstance()->get(DateTimeFactory::class);
                $date = $dateTimeFactory->create('now', new \DateTimeZone('UTC'));
                $row['low_stock_date'] = $date->format(DateTime::DATETIME_PHP_FORMAT);
            }
            $row['stock_status_changed_auto'] = (int)!$this->stockStateProvider->verifyStock($stockItemDo);
        } else {
            $row['qty'] = 0;
        }

        return $row;
    }

    /**
     * Get row stock item model
     *
     * @param array $rowData
     * @return StockItemInterface
     */
    private function getRowExistingStockItem(array $rowData): StockItemInterface
    {
        $productId = $this->skuProcessor->getNewSku($rowData[self::COL_SKU])['entity_id'];
        $websiteId = $this->stockConfiguration->getDefaultScopeId();
        return $this->stockRegistry->getStockItem($productId, $websiteId);
    }

    /**
     * @param array $rowData
     * @return array
     */
    protected function processRowCategories($rowData)
    {
        $categoriesString = empty($rowData[self::COL_CATEGORY]) ? '' : $rowData[self::COL_CATEGORY];
        /*Get categories from additional category columns*/
        $categoryKey = 'category';
        $i = 0;
        while(true){
            $i ++;
            $column = $categoryKey."_".$i;
            if (empty($rowData[$column])) break;
            $categoryName = trim($rowData[$column]);
            $categoriesString.= $this->getMultipleValueSeparator().$categoryName;
        }

        $categoryIds = [];
        if (!empty($categoriesString)) {
            $categoryIds = $this->categoryProcessor->upsertCategories(
                $categoriesString,
                $this->getMultipleValueSeparator()
            );
            foreach ($this->categoryProcessor->getFailedCategories() as $error) {
                $this->errorAggregator->addError(
                    AbstractEntity::ERROR_CODE_CATEGORY_NOT_VALID,
                    ProcessingError::ERROR_LEVEL_NOT_CRITICAL,
                    $rowData['rowNum'],
                    self::COL_CATEGORY,
                    $error['exception']->getMessage()
                );
            }
            $this->categoryProcessor->resetFailCategories();
        }
        return $categoryIds;
    }

    /**
     * Returns an object for upload a media files
     *
     * @return \Magento\CatalogImportExport\Model\Import\Uploader
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _getUploader()
    {
        if (is_null($this->_fileUploader)) {
            $this->_fileUploader = $this->_uploaderFactory->create();

            $this->_fileUploader->init();

            $dirConfig = DirectoryList::getDefaultConfig();
            $dirAddon = $dirConfig[DirectoryList::MEDIA][DirectoryList::PATH];

            $DS = DIRECTORY_SEPARATOR;

            $tmpPath = $dirAddon . $DS . $this->_mediaDirectory->getRelativePath('vnecoms_import/'.$this->getVendor()->getVendorId());

            if(!file_exists($tmpPath)){
                @mkdir($tmpPath, 0777, true);
            }

            if (!$this->_fileUploader->setTmpDir($tmpPath)) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('File directory \'%1\' is not readable.', $tmpPath)
                );
            }
            $destinationDir = "catalog/product";
            $destinationPath = $dirAddon . $DS . $this->_mediaDirectory->getRelativePath($destinationDir);

            $this->_mediaDirectory->create($destinationPath);
            if (!$this->_fileUploader->setDestDir($destinationPath)) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('File directory \'%1\' is not writable.', $destinationPath)
                );
            }
        }
        return $this->_fileUploader;
    }

    /**
     * Retrieves escaped PSEUDO_MULTI_LINE_SEPARATOR if it is metacharacter for regular expression
     *
     * @return string
     */
    private function getMultiLineSeparatorForRegexp()
    {
        if (!$this->multiLineSeparatorForRegexp) {
            $this->multiLineSeparatorForRegexp = in_array(self::PSEUDO_MULTI_LINE_SEPARATOR, str_split('[\^$.|?*+(){}'))
                ? '\\' . self::PSEUDO_MULTI_LINE_SEPARATOR
                : self::PSEUDO_MULTI_LINE_SEPARATOR;
        }
        return $this->multiLineSeparatorForRegexp;
    }

    /**
     * Set values in use_config_ fields.
     *
     * @param array $rowData
     *
     * @return array
     */
    private function _setStockUseConfigFieldsValues($rowData)
    {
        $useConfigFields = array();
        foreach ($rowData as $key => $value) {
            $useConfigName = self::INVENTORY_USE_CONFIG_PREFIX . $key;
            if (isset($this->defaultStockData[$key])
                && isset($this->defaultStockData[$useConfigName])
                && !empty($value)
                && empty($rowData[$useConfigName])
            ) {
                $useConfigFields[$useConfigName] = ($value == self::INVENTORY_USE_CONFIG) ? 1 : 0;
            }
        }
        $rowData = array_merge($rowData, $useConfigFields);
        return $rowData;
    }

    /**
     * Custom fields mapping for changed purposes of fields and field names.
     *
     * @param array $rowData
     *
     * @return array
     */
    private function _customFieldsMapping($rowData)
    {
        foreach ($this->_fieldsMap as $systemFieldName => $fileFieldName) {
            if (array_key_exists($fileFieldName, $rowData)) {
                $rowData[$systemFieldName] = $rowData[$fileFieldName];
            }
        }

        $rowData = $this->_parseAdditionalAttributes($rowData);

        $rowData = $this->_setStockUseConfigFieldsValues($rowData);
        if (array_key_exists('status', $rowData)
            && $rowData['status'] != \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED
        ) {
            if ($rowData['status'] == 'yes') {
                $rowData['status'] = \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED;
            } elseif (!empty($rowData['status']) || $this->getRowScope($rowData) == self::SCOPE_DEFAULT) {
                $rowData['status'] = \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_DISABLED;
            }
        }
        return $rowData;
    }


    /**
     * @param array $rowData
     * @return bool
     */
    private function isNeedToValidateUrlKey($rowData)
    {
        return (!empty($rowData[self::URL_KEY]) || !empty($rowData[self::COL_NAME]))
            && (empty($rowData[self::COL_VISIBILITY])
                || $rowData[self::COL_VISIBILITY]
                !== (string)Visibility::getOptionArray()[Visibility::VISIBILITY_NOT_VISIBLE]);
    }

    /**
     * Prepare new SKU data
     *
     * @param string $sku
     * @return array
     */
    private function prepareNewSkuData($sku)
    {
        $data = [];
        foreach ($this->getExistingSku($sku) as $key => $value) {
            $data[$key] = $value;
        }

        $data['attr_set_code'] = $this->_attrSetIdToName[$this->getExistingSku($sku)['attr_set_id']];

        return $data;
    }

    /**
     * Parse attributes names and values string to array.
     *
     * @param array $rowData
     *
     * @return array
     */
    private function _parseAdditionalAttributes($rowData)
    {
        if (empty($rowData['additional_attributes'])) {
            return $rowData;
        }
        $rowData = array_merge($rowData, $this->parseAdditionalAttributes($rowData['additional_attributes']));
        return $rowData;
    }

    /**
     * Retrieves additional attributes in format:
     * [
     *      code1 => value1,
     *      code2 => value2,
     *      ...
     *      codeN => valueN
     * ]
     *
     * @param string $additionalAttributes Attributes data that will be parsed
     * @return array
     */
    private function parseAdditionalAttributes($additionalAttributes)
    {
        return empty($this->_parameters[Import::FIELDS_ENCLOSURE])
            ? $this->parseAttributesWithoutWrappedValues($additionalAttributes)
            : $this->parseAttributesWithWrappedValues($additionalAttributes);
    }

    /**
     * Parses data and returns attributes in format:
     * [
     *      code1 => value1,
     *      code2 => value2,
     *      ...
     *      codeN => valueN
     * ]
     *
     * @param string $attributesData Attributes data that will be parsed. It keeps data in format:
     *      code=value,code2=value2...,codeN=valueN
     * @return array
     */
    private function parseAttributesWithoutWrappedValues($attributesData)
    {
        $attributeNameValuePairs = explode($this->getMultipleValueSeparator(), $attributesData);
        $preparedAttributes = [];
        $code = '';
        foreach ($attributeNameValuePairs as $attributeData) {
            //process case when attribute has ImportModel::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR inside its value
            if (strpos($attributeData, self::PAIR_NAME_VALUE_SEPARATOR) === false) {
                if (!$code) {
                    continue;
                }
                $preparedAttributes[$code] .= $this->getMultipleValueSeparator() . $attributeData;
                continue;
            }
            list($code, $value) = explode(self::PAIR_NAME_VALUE_SEPARATOR, $attributeData, 2);
            $preparedAttributes[$code] = $value;
        }
        return $preparedAttributes;
    }

    /**
     * Parses data and returns attributes in format:
     * [
     *      code1 => value1,
     *      code2 => value2,
     *      ...
     *      codeN => valueN
     * ]
     * All values have unescaped data except mupliselect attributes,
     * they should be parsed in additional method - parseMultiselectValues()
     *
     * @param string $attributesData Attributes data that will be parsed. It keeps data in format:
     *      code="value",code2="value2"...,codeN="valueN"
     *  where every value is wrapped in double quotes. Double quotes as part of value should be duplicated.
     *  E.g. attribute with code 'attr_code' has value 'my"value'. This data should be stored as attr_code="my""value"
     *
     * @return array
     */
    private function parseAttributesWithWrappedValues($attributesData)
    {
        $attributes = [];
        preg_match_all('~((?:[a-z0-9_])+)="((?:[^"]|""|"' . $this->getMultiLineSeparatorForRegexp() . '")+)"+~',
            $attributesData,
            $matches
        );
        foreach ($matches[1] as $i => $attributeCode) {
            $attribute = $this->retrieveAttributeByCode($attributeCode);
            $value = 'multiselect' != $attribute->getFrontendInput()
                ? str_replace('""', '"', $matches[2][$i])
                : '"' . $matches[2][$i] . '"';
            $attributes[$attributeCode] = $value;
        }
        return $attributes;
    }


    protected $_productEntityLinkField;
    /**
     * Get product entity link field
     *
     * @return string
     */
    private function getProductEntityLinkField()
    {
        if (!$this->_productEntityLinkField) {
            $this->_productEntityLinkField = $this->getMetadataPool()
                ->getMetadata(\Magento\Catalog\Api\Data\ProductInterface::class)
                ->getLinkField();
        }
        return $this->_productEntityLinkField;
    }


    protected $_productEntityIdentifierField;
    /**
     * Get product entity identifier field
     *
     * @return string
     */
    private function getProductIdentifierField()
    {
        if (!$this->_productEntityIdentifierField) {
            $this->_productEntityIdentifierField = $this->getMetadataPool()
                ->getMetadata(\Magento\Catalog\Api\Data\ProductInterface::class)
                ->getIdentifierField();
        }
        return $this->_productEntityIdentifierField;
    }

    /**
     * Check if product exists for specified SKU
     *
     * @param string $sku
     * @return bool
     */
    private function isSkuExist($sku)
    {
        $sku = strtolower($sku);
        return isset($this->_oldSku[$sku]);
    }

    /**
     * Get existing product data for specified SKU
     *
     * @param string $sku
     * @return array
     */
    private function getExistingSku($sku)
    {
        return $this->_oldSku[strtolower($sku)];
    }
}
