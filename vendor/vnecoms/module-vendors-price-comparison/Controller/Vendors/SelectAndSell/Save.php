<?php
/**
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsPriceComparison\Controller\Vendors\SelectAndSell;

use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\Filter\Date;
use Vnecoms\VendorsProduct\Model\Source\Approval;
use Zend\Stdlib\Parameters;

class Save extends \Vnecoms\VendorsProduct\Controller\Vendors\Product
{
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
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * These attribute will not be checked for approval
     *
     * @var unknown
     */
    protected $notCheckAttributes = [
        'media_gallery',
        'quantity_and_stock_status',
        'image',
        'small_image',
        'thumbnail',
        'affect_product_custom_options',
        'options'
    ];

    /**
     *
     * @param \Vnecoms\Vendors\App\Action\Context $context
     * @param \Vnecoms\Vendors\App\ConfigInterface $config
     * @param Registry $coreRegistry
     * @param Date $dateFilter
     * @param \Magento\Catalog\Controller\Adminhtml\Product\Builder $productBuilder
     * @param \Vnecoms\VendorsPriceComparison\Controller\Vendors\SelectAndSell\Initialization\Helper $initializationHelper
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory
     */
    public function __construct(
        \Vnecoms\Vendors\App\Action\Context $context,
        \Magento\Catalog\Controller\Adminhtml\Product\Builder $productBuilder,
        \Vnecoms\VendorsPriceComparison\Controller\Vendors\SelectAndSell\Initialization\Helper $initializationHelper,
        \Magento\Catalog\Model\Product\Copier $productCopier,
        \Magento\Catalog\Model\Product\TypeTransitionManager $productTypeManager,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Vnecoms\VendorsProduct\Helper\Data $vendorProductHelper
    ) {
        parent::__construct($context, $productBuilder);
        $this->storeManager         = $storeManager;
        $this->initializationHelper = $initializationHelper;
        $this->productCopier        = $productCopier;
        $this->productTypeManager   = $productTypeManager;
        $this->productRepository    = $productRepository;
        $this->vendorProductHelper  = $vendorProductHelper;
    }


    /**
     * Save product action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        $storeId = $this->getRequest()->getParam('store');
        $redirectBack = $this->getRequest()->getParam('back', false);

        $productId = $this->getRequest()->getParam('id');
        $resultRedirect = $this->resultRedirectFactory->create();

        $data = $this->getRequest()->getPostValue();

        /*Unset all values from not allow attributes (if exist)*/
        foreach ($this->vendorProductHelper->getNotUsedVendorAttributes() as $attribute) {
            unset($data['product'][$attribute]);
        }
        if(isset($data['product']['stock_data']['item_id'])) unset($data['product']['stock_data']['item_id']);
        if(isset($data['product']['stock_data']['product_id'])) unset($data['product']['stock_data']['product_id']);
        if(isset($data['product']['stock_data']['stock_id'])) unset($data['product']['stock_data']['stock_id']);
        unset($data['id']);
        /*Set the curent website id*/
        if(!$this->vendorProductHelper->canVendorSetWebsite()){
            /*Set the curent website id*/
            $data['product']['website_ids'] = [$this->storeManager->getWebsite()->getId() => $this->storeManager->getWebsite()->getId()];
        }
        $data = $this->_processPreSpecialAttribute($data);
        $post = new Parameters($data);
        $this->getRequest()->setParam('id', null);
        $this->getRequest()->setPost($post);

        $productAttributeSetId = $this->getRequest()->getParam('set');
        if ($data) {
            try {
                $product = $this->productBuilder->build($this->getRequest());

                $data = $this->_processBeforeSpecialAttribute($data, $product);
                $post = new Parameters($data);
                $this->getRequest()->setPost($post);

                $product->setId(null);

                /*Set vendor ID and save*/
                $product->setVendorId($this->_session->getVendor()->getId());


                $product = $this->initializationHelper->initialize($product);
                $this->productTypeManager->processProduct($product);

                /*Set the curent website id*/
                if(!$this->vendorProductHelper->canVendorSetWebsite()){
                    /*Set the curent website id*/
                    $product->setWebsiteIds([$this->storeManager->getWebsite()->getId() => $this->storeManager->getWebsite()->getId()]);
                }

                $savedraft = $this->getRequest()->getParam('savedraft', false);

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

                if (isset($data['product'][$product->getIdFieldName()])) {
                    throw new \Magento\Framework\Exception\LocalizedException(__('Unable to save product'));
                }

                $originalSku = $product->getSku();
                $product->setData('select_from_product_id',$productId);

                $product->save();

                $this->_eventManager->dispatch(
                    'controller_action_add_product_comparison_after',
                    [
                        'product' => $product,
                        'main_product_id' => $productId
                    ]
                );

                $this->handleImageRemoveError($data, $product->getId());
                $productId = $product->getId();
                $productAttributeSetId = $product->getAttributeSetId();

                $this->messageManager->addSuccess(__('You saved the product.'));
                if ($product->getSku() != $originalSku) {
                    $this->messageManager->addNotice(
                        __(
                            'SKU for product %1 has been changed to %2.',
                            $this->_objectManager->get('Magento\Framework\Escaper')->escapeHtml($product->getName()),
                            $this->_objectManager->get('Magento\Framework\Escaper')->escapeHtml($product->getSku())
                        )
                    );
                }

                $this->_eventManager->dispatch(
                    'controller_action_catalog_product_save_entity_after',
                    ['controller' => $this, 'product' => $product]
                );

            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
                $this->_session->setProductData($data);
                $redirectBack = $productId ? true : 'new';
            } catch (\Exception $e) {
                $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
                $this->messageManager->addError($e->getMessage());
                $this->_session->setProductData($data);
                $redirectBack = $productId ? true : 'new';
            }
        } else {
            $resultRedirect->setPath('catalog/product/index', ['store' => $storeId]);
            $this->messageManager->addError('No data to save');
            return $resultRedirect;
        }

        if ($redirectBack) {
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
     * Notify customer when image was not deleted in specific case.
     * TODO: temporary workaround must be eliminated in MAGETWO-45306
     *
     * @param array $postData
     * @param int $productId
     * @return void
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
                if ($expectedImagesAmount != count($product->getMediaGallery('images'))) {
                    $this->messageManager->addNotice(
                        __('The image cannot be removed as it has been assigned to the other image role')
                    );
                }
            }
        }
    }

    /**
     * @param $data
     * @return mixed
     */
    private function _processPreSpecialAttribute($data) {
        if(isset($data["product"]["options"])) {
            foreach ($data["product"]["options"] as &$options) {
                if (isset($options["option_id"])) unset($options["option_id"]);
                if (isset($options["values"])) {
                    foreach ($options["values"] as &$value) {
                        if (isset($value["option_id"])) unset($value["option_id"]);
                        if (isset($value["option_type_id"])) unset($value["option_type_id"]);
                    }
                }
            }
        }
        return $data;
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
            $data['product']["name"] = md5(time() .'-'.rand(10000000,99999999));
        }

        $urlKey = $product->formatUrlKey($data['product']["name"]);
        if(!$urlKey){
            $urlKey = md5(time() .'-'.rand(10000000,99999999));
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
