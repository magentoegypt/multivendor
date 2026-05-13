<?php

namespace Vnecoms\VendorsProduct\Controller\Vendors\Product;

use Laminas\Stdlib\Parameters;
use Vnecoms\VendorsProduct\Model\Source\Approval;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Catalog\Api\Data\ProductInterface;

class Save extends \Vnecoms\VendorsProduct\Controller\Vendors\Product
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_Vendors::product_action_save';

    /**
     * @var \Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper
     */
    protected $initializationHelper;

    /**
     * @var \Magento\Catalog\Model\Product\Copier
     */
    protected $productCopier;

    /**
     * @var \Magento\Catalog\Model\Product\TypeTransitionManager
     */
    protected $productTypeManager;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Vnecoms\VendorsProduct\Helper\Data
     */
    protected $vendorProductHelper;

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Catalog\Api\CategoryLinkManagementInterface
     */
    protected $categoryLinkManagement;

    /**
     * @var \Magento\Framework\Escaper|null
     */
    private $escaper;

    /**
     * @var null|\Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var \Vnecoms\VendorsProduct\Model\Product\ProcessUpdateAttribute
     */
    protected $processUpdateAttribute;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Serialize
     */
    protected $serializer;

    /**
     * Save constructor.
     * @param \Vnecoms\Vendors\App\Action\Context $context
     * @param \Magento\Catalog\Controller\Adminhtml\Product\Builder $productBuilder
     * @param \Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper $initializationHelper
     * @param \Magento\Catalog\Model\Product\Copier $productCopier
     * @param \Magento\Catalog\Model\Product\TypeTransitionManager $productTypeManager
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Vnecoms\VendorsProduct\Helper\Data $vendorProductHelper
     * @param \Vnecoms\VendorsProduct\Model\Product\ProcessUpdateAttribute $processUpdateAttribute
     * @param \Magento\Framework\Serialize\Serializer\Serialize $serializer
     * @param \Magento\Framework\Escaper|null $escaper
     * @param \Psr\Log\LoggerInterface|null $logger
     */
    public function __construct(
        \Vnecoms\Vendors\App\Action\Context $context,
        \Magento\Catalog\Controller\Adminhtml\Product\Builder $productBuilder,
        \Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper $initializationHelper,
        \Magento\Catalog\Model\Product\Copier $productCopier,
        \Magento\Catalog\Model\Product\TypeTransitionManager $productTypeManager,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Vnecoms\VendorsProduct\Helper\Data $vendorProductHelper,
        \Vnecoms\VendorsProduct\Model\Product\ProcessUpdateAttribute $processUpdateAttribute,
        \Magento\Framework\Serialize\Serializer\Serialize $serializer,
        \Magento\Framework\Escaper $escaper = null,
        \Psr\Log\LoggerInterface $logger = null
    ) {
        parent::__construct($context, $productBuilder);
        $this->storeManager         = $storeManager;
        $this->initializationHelper = $initializationHelper;
        $this->productCopier        = $productCopier;
        $this->productTypeManager   = $productTypeManager;
        $this->productRepository    = $productRepository;
        $this->vendorProductHelper  = $vendorProductHelper;
        $this->processUpdateAttribute = $processUpdateAttribute;
        $this->serializer =$serializer;
        $this->escaper = $escaper ?: $this->_objectManager->get(\Magento\Framework\Escaper::class);
        $this->logger = $logger ?: $this->_objectManager->get(\Psr\Log\LoggerInterface::class);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        $storeId = $this->getRequest()->getParam('store', 0);
        $currentStore = $this->storeManager->getStore($storeId);
        $redirectBack = $this->getRequest()->getParam('back', false);
        $productAttributeSetId = $this->getRequest()->getParam('set');
        $productTypeId = $this->getRequest()->getParam('type');
        $productId = $this->getRequest()->getParam('id');
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();

        /*Unset all values from not allow attributes (if exist)*/
        foreach ($this->vendorProductHelper->getNotUsedVendorAttributes() as $attribute) {
            unset($data['product'][$attribute]);
        }

        if ($data) {
            try {
                //$params = $this->getRequest()->getParams();
                $product = $this->productBuilder->build($this->getRequest());

                $data = $this->_processBeforeSpecialAttribute($data, $product);
                $post = new Parameters($data);
                $this->getRequest()->setPost($post);

                /*Set vendor ID and save*/
                $product->setVendorId($this->_session->getVendor()->getId());

                if(!$this->vendorProductHelper->canVendorSetWebsite()){
                    /*Set the curent website id*/
                    $product->setWebsiteIds([$this->storeManager->getWebsite()->getId() => $this->storeManager->getWebsite()->getId()]);
                    $post = $this->getRequest()->getPost();
                    $productData = $post->get('product', []);
                    if(!isset($productData['category_ids'])){
                        $productData['category_ids'] = $product->getCategoryIds(); /*If the category attribute is hidden from vendor panel,  use current saved value.*/
                    }
                    $productData['website_ids'] = $this->storeManager->getWebsite()->getId();
                    $post->set('product', $productData);
                    $this->getRequest()->setPost($post);
                }

                $product = $this->initializationHelper->initialize($product);
                $this->_processAfterSpecialAttribute($product);
                $this->productTypeManager->processProduct($product);

                /*Update Approval Attribute*/
                $savedraft = $this->getRequest()->getParam('savedraft', false);

                /*
                 * If this flag is set to false, the product will not be saved
                 * This is used for update approval feature so updated product will not be affacted immediately.
                 * It needs admin to approve to apply the changes.
                */
                $saveProductFlag = true;

                if ($product->getId()) {
                    /*
                     * Update product info
                     * If the product is already pending just do nothing.
                     */
                    if ($this->vendorProductHelper->isUpdateProductsApproval()) {
                        if (!in_array($product->getApproval(), [Approval::STATUS_PENDING, Approval::STATUS_NOT_SUBMITED, Approval::STATUS_UNAPPROVED])) {
                            $changedData = $this->processUpdateAttribute->getChangedData($product);
                            //var_dump($changedData);exit;
                            if (sizeof($changedData)) {
                                $saveProductFlag = false;
                                /*Save changed data*/
                                $update = $this->_objectManager->create('Vnecoms\VendorsProduct\Model\Product\Update');

                                /*Check if there is an exist pending update*/
                                $collection = $update->getCollection()
                                    ->addFieldToFilter('vendor_id', $this->_session->getVendor()->getId())
                                    ->addFieldToFilter('store_id', $this->getRequest()->getParam('store', 0))
                                    ->addFieldToFilter('product_id', $product->getId())
                                    ->addFieldToFilter('status', \Vnecoms\VendorsProduct\Model\Product\Update::STATUS_PENDING);
                                if ($collection->count()) {
                                    /*Update changed data*/
                                    $update = $collection->getFirstItem();
                                    $update->setProductData($this->serializer->serialize($changedData));
                                    $update->setId($update->getUpdateId())->save();
                                } else {
                                    $update->setData([
                                        'vendor_id' => $this->_session->getVendor()->getId(),
                                        'store_id' => $this->getRequest()->getParam('store', 0),
                                        'product_id' => $product->getId(),
                                        'product_data' => $this->serializer->serialize($changedData),
                                        'status' => \Vnecoms\VendorsProduct\Model\Product\Update::STATUS_PENDING
                                    ])->save();
                                }

                                if (!$savedraft) {
                                    $product->setApproval(Approval::STATUS_PENDING_UPDATE)
                                        ->getResource()
                                        ->saveAttribute($product, 'approval');
                                    $this->vendorProductHelper->sendUpdateProductApprovalEmailToAdmin($product, $this->_getSession()->getVendor());
                                }
                            }
                        } else {
                            if (!$savedraft) {
                                if ($product->getApproval() != Approval::STATUS_PENDING) {
                                    $this->vendorProductHelper->sendUpdateProductApprovalEmailToAdmin($product, $this->_getSession()->getVendor());
                                }

                                $product->setApproval(Approval::STATUS_PENDING)
                                    ->getResource()
                                    ->saveAttribute($product, 'approval');
                            }
                        }
                    } else {
                        if ($product->getApproval() == Approval::STATUS_PENDING_UPDATE) {
                            $product->setApproval(Approval::STATUS_APPROVED);
                        }
                    }
                } else {

                    /*Add new product*/
                    if (!$this->vendorProductHelper->isNewProductsApproval()) {
                        $product->setApproval(Approval::STATUS_APPROVED);
                    }
                    else{
                        if ($savedraft) {
                            $product->setApproval(Approval::STATUS_NOT_SUBMITED);
                        } elseif ($this->vendorProductHelper->isNewProductsApproval()) {
                            $product->setApproval(Approval::STATUS_PENDING);
                            /*Send new product approval notification email to admin*/
                            $this->vendorProductHelper->sendNewProductApprovalEmailToAdmin($product, $this->_getSession()->getVendor());
                        }
                    }
                }

                if (isset($data['product'][$product->getIdFieldName()])) {
                    throw new \Magento\Framework\Exception\LocalizedException(__('Unable to save product'));
                }

                $originalSku = $product->getSku();

                if ($saveProductFlag) {
                    $product->save();

                    $this->getCategoryLinkManagement()->assignProductToCategories(
                        $product->getSku(),
                        $product->getCategoryIds()
                    );
                } else {
                    $tmpProduct = $this->_objectManager->create('Magento\Catalog\Model\Product')
                        ->load($product->getId())->setStoreId($this->getRequest()->getParam('store', 0));
                    $oldData = $tmpProduct->getData();
                    $productData = $this->getRequest()->getPost('product', []);

                    if (!$this->vendorProductHelper->getUpdateProductsApprovalFlag()) {
                        $changedData = [];
                        $notCheckAttributes = $this->vendorProductHelper->getIgnoreUpdateApprovalProductAttributes();
                        $notCheckAttributes = array_merge($this->vendorProductHelper->getUpdateProductsApprovalAttributes(), $notCheckAttributes);
                        foreach ($notCheckAttributes as $attributeCode) {
                            if (isset($productData[$attributeCode])) {
                                $changedData[$attributeCode] = $productData[$attributeCode];
                            }
                        }
                    }else{
                        $changedData = $productData;
                        $checkAttributes = $this->vendorProductHelper->getUpdateProductsApprovalAttributes();
                        foreach ($checkAttributes as $attributeCode) {
                            if (isset($changedData[$attributeCode])) {
                                unset($changedData[$attributeCode]);
                            }
                        }
                    }

                    // Add save product flag for later use if need
                    $changedData['save_product_flag'] = $saveProductFlag;
                    $changedData = array_merge($oldData, $changedData);

                    $tmpProduct = $this->initializationHelper->initializeFromData($tmpProduct, $changedData);

                    $this->_objectManager->create('Vnecoms\VendorsProduct\Controller\Vendors\Product\Initialization\Helper')->initialize($tmpProduct);
                    $this->productTypeManager->processProduct($tmpProduct);
                    /*Set vendor ID and save*/
                    $tmpProduct->setVendorId($this->_session->getVendor()->getId());

                    $websiteIds = isset($productData['website_ids'])?$productData['website_ids']:[];
                    if(!$this->vendorProductHelper->canVendorSetWebsite()){
                        /*Set the curent website id*/
                        $websiteIds = [$this->storeManager->getWebsite()->getId() => $this->storeManager->getWebsite()->getId()];
                    }
                    $tmpProduct->setWebsiteIds($websiteIds);

                    $tmpProduct->save();
                }

                $this->handleImageRemoveError($data, $product->getId());
                $productId = $product->getId();
                $productAttributeSetId = $product->getAttributeSetId();
                $productTypeId = $product->getTypeId();


                $canSaveCustomOptions = $product->getCanSaveCustomOptions();
                $data['can_save_custom_options'] = $canSaveCustomOptions;

                /**
                 * Do copying data to stores
                 */
                $this->copyToStores($data, $productId);

                $this->messageManager->addSuccess(__('You saved the product.'));
                if ($product->getSku() != $originalSku) {
                    $this->messageManager->addNotice(
                        __(
                            'SKU for product %1 has been changed to %2.',
                            $this->escaper->escapeHtml($product->getName()),
                            $this->escaper->escapeHtml($product->getSku())
                        )
                    );
                }
                $this->getDataPersistor()->clear('catalog_product');
                $this->_eventManager->dispatch(
                    'controller_action_catalog_product_save_entity_after',
                    ['controller' => $this, 'product' => $product]
                );

                if ($redirectBack === 'duplicate') {
                    $newProduct = $this->productCopier->copy($product);
                    $this->messageManager->addSuccess(__('You duplicated the product.'));
                }
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->logger->critical($e);
                $this->messageManager->addError($e->getMessage());
                $data = isset($product) ? $this->persistMediaData($product, $data) : $data;
                $this->getDataPersistor()->set('catalog_product', $data);
                $redirectBack = $productId ? true : 'new';
            } catch (\Exception $e) {
                $this->logger->critical($e);
                $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
                $this->messageManager->addError($e->getMessage());
                $data = isset($product) ? $this->persistMediaData($product, $data) : $data;
                $this->getDataPersistor()->set('catalog_product', $data);
                $redirectBack = $productId ? true : 'new';
            }
        } else {
            $resultRedirect->setPath('catalog/product/index', ['store' => $storeId]);
            $this->messageManager->addError('No data to save');
            return $resultRedirect;
        }

        if($currentStore->getId()){
            $this->_url->setData('scope', $currentStore);
        }

        if ($redirectBack === 'new') {
            $resultRedirect->setPath(
                'catalog/product/new',
                ['set' => $productAttributeSetId, 'type' => $productTypeId]
            );
        } elseif ($redirectBack === 'duplicate' && isset($newProduct)) {
            $resultRedirect->setPath(
                'catalog/product/edit',
                ['id' => $newProduct->getId(), 'back' => null, '_current' => true]
            );
        } elseif ($redirectBack) {
            $resultRedirect->setPath(
                'catalog/product/edit',
                ['id' => $productId, '_current' => true, 'set' => $productAttributeSetId]
            );
        } else {
            $resultRedirect->setPath('catalog/product', ['store' => $storeId]);
        }
        return $resultRedirect;
    }

    /**
     * Do copying data to stores
     *
     * @param array $data
     * @param int $productId
     * @return void
     */
    private function copyToStores($data, $productId)
    {
        if (isset($data['copy_to_stores'])) {
            foreach ($data['copy_to_stores'] as $storeTo => $storeFrom) {
                $this->_objectManager->create('Magento\Catalog\Model\Product')
                    ->setStoreId($storeFrom)
                    ->load($productId)
                    ->setStoreId($storeTo)
                    ->save();
            }
        }
    }

    /**
     * @param $postData
     * @param $productId
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function handleImageRemoveError($postData, $productId)
    {
        if (isset($postData['product']['media_gallery']['images'])) {
            $removedImagesAmount = 0;
            foreach ($postData['product']['media_gallery']['images'] as $image) {
                if (!empty($image['removed'])) {
                    $removedImagesAmount++;
                }
            }
            if ($removedImagesAmount) {
                $expectedImagesAmount = count($postData['product']['media_gallery']['images']) - $removedImagesAmount;
                $product = $this->productRepository->getById($productId);
                $images = $product->getMediaGallery('images');
                if (is_array($images) && $expectedImagesAmount != count($images)) {
                    $this->messageManager->addNoticeMessage(
                        __('The image cannot be removed as it has been assigned to the other image role')
                    );
                }
            }
        }
    }

    /**
     * Retrieve data persistor
     *
     * @return DataPersistorInterface|mixed
     * @deprecated
     */
    protected function getDataPersistor()
    {
        if (null === $this->dataPersistor) {
            $this->dataPersistor = $this->_objectManager->get(DataPersistorInterface::class);
        }

        return $this->dataPersistor;
    }

    /**
     * @return \Magento\Catalog\Api\CategoryLinkManagementInterface
     */
    private function getCategoryLinkManagement()
    {
        if (null === $this->categoryLinkManagement) {
            $this->categoryLinkManagement = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\Catalog\Api\CategoryLinkManagementInterface::class);
        }
        return $this->categoryLinkManagement;
    }

    /**
     * Persist media gallery on error, in order to show already saved images on next run.
     *
     * @param ProductInterface $product
     * @param array $data
     * @return array
     */
    private function persistMediaData(ProductInterface $product, array $data)
    {
        $mediaGallery = $product->getData('media_gallery');
        if (!empty($mediaGallery['images'])) {
            foreach ($mediaGallery['images'] as $key => $image) {
                if (!isset($image['new_file'])) {
                    //Remove duplicates.
                    unset($mediaGallery['images'][$key]);
                }
            }
            $data['product']['media_gallery'] = $mediaGallery;
            $fields = [
                'image',
                'small_image',
                'thumbnail',
                'swatch_image',
            ];
            foreach ($fields as $field) {
                $data['product'][$field] = $product->getData($field);
            }
        }

        return $data;
    }

    /**
     * @param $product
     */
    private function _processAfterSpecialAttribute($product) {
        /*Set vendor ID and save*/
        $product->setVendorId($this->_session->getVendor()->getId());
    }

    /**
     * @param $data
     * @return mixed
     */
    private function _processBeforeSpecialAttribute($data, $product) {
        $ignoreAttribute = $this->vendorProductHelper->getNotUsedVendorAttributes();
        if (in_array("quantity_and_stock_status", $ignoreAttribute)) {
            $data['product']['stock_data']['manage_stock'] = 0;
            $data['product']['stock_data']['use_config_manage_stock'] = 0;
        }
        if (!isset($data['product']["name"])) {
            $data['product']["name"] = base64_encode(time() .'-'.rand(10000000,99999999));
        }

        $urlKey = $product->formatUrlKey($data['product']["name"]);
        if(!$urlKey){
            $urlKey = base64_encode(time() .'-'.rand(10000000,99999999));
        }else{
            $urlKey .= '-'.rand(10000000,99999999);
        }

        if (!isset($data['product']["sku"]) && in_array("sku", $ignoreAttribute)) {
            if ($product->getSku()) {
                $data['product']["sku"] = $product->getSku();
            } else {
                $data['product']["sku"] = $urlKey;
            }
        }

        if (!isset($data['product']["url_key"]) && in_array("url_key", $ignoreAttribute) ) {
            if ($product->getUrlKey()) {
                $data['product']["url_key"] = $product->getUrlKey();
            } else {
                $data['product']["url_key"] = $urlKey;
            }
        }

        return $data;
    }
}
