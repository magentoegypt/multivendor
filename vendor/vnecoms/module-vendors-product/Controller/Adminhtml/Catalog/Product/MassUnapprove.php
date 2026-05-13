<?php
/**
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsProduct\Controller\Adminhtml\Catalog\Product;

use Magento\Framework\Controller\ResultFactory;
use Magento\Catalog\Controller\Adminhtml\Product\Builder;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Vnecoms\VendorsProduct\Model\Source\Approval;
use Vnecoms\VendorsProduct\Model\Product\Update as ProductUpdate;

class MassUnapprove extends \Magento\Catalog\Controller\Adminhtml\Product
{
    /**
     * Massactions filter
     *
     * @var Filter
     */
    protected $filter;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var \Vnecoms\VendorsProduct\Helper\Data
     */
    protected $productHelper;

    /**
     * @var \Vnecoms\Vendors\Model\VendorFactory
     */
    protected $vendorFactory;

    /**
     * @var \Magento\Framework\Indexer\IndexerRegistry
     */
    protected $indexerRegistry;

    /**
     * MassUnapprove constructor.
     * @param Context $context
     * @param Builder $productBuilder
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param \Vnecoms\VendorsProduct\Helper\Data $productHelper
     * @param \Vnecoms\Vendors\Model\VendorFactory $vendorFactory
     * @param \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry
     */
    public function __construct(
        Context $context,
        Builder $productBuilder,
        Filter $filter,
        CollectionFactory $collectionFactory,
        \Vnecoms\VendorsProduct\Helper\Data $productHelper,
        \Vnecoms\Vendors\Model\VendorFactory $vendorFactory,
        \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry
    ) {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->productHelper = $productHelper;
        $this->vendorFactory = $vendorFactory;
        $this->indexerRegistry = $indexerRegistry;
        parent::__construct($context, $productBuilder);
    }

    /**
     * Approve Update Changes of an Exist product
     * @param \Magento\Catalog\Model\Product $product
     */
    protected function unapproveExistProduct(
        \Magento\Catalog\Model\Product $product,
        \Vnecoms\Vendors\Model\Vendor $vendor
    ) {
        $updateCollection = $this->_objectManager->create('Vnecoms\VendorsProduct\Model\ResourceModel\Product\Update\Collection');
        $updateCollection->addFieldToFilter('product_id', $product->getId())
            ->addFieldToFilter('status', ProductUpdate::STATUS_PENDING);

        $allIds = $updateCollection->getAllIds();
        
        foreach ($updateCollection as $update) {
            $update->setStatus(ProductUpdate::STATUS_UNAPPROVED)->setId($update->getUpdateId())->save();
        }

        /*Send update product notification email*/
        $queue =  \Magento\Framework\App\ObjectManager::getInstance()
            ->create('Vnecoms\VendorsProduct\Model\Queue');
        $queue->publish(
            'update_reject_product',
            $allIds,
            $vendor->getId(),
            $product->getId()
        );

    }

    /**
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $productApproved = 0;
        $vendors = [];

        foreach ($collection->getItems() as $product) {
            $product->load($product->getId());
            $vendorId = $product->getVendorId();

            if (!$vendorId) {
                continue;
            }

            if (!isset($vendors[$vendorId])) {
                $vendor = $this->vendorFactory->create();
                $vendor->load($vendorId);
                $vendors[$vendorId] = $vendor;
            }

            $vendor = $vendors[$vendorId];

            $approval = Approval::STATUS_UNAPPROVED;
            /**
             * Approve Pending updates.
             */
            if ($product->getApproval() == Approval::STATUS_PENDING_UPDATE) {
                $this->unapproveExistProduct($product, $vendor);
                $message = __('Updates of %1 are rejected', '<strong>'.$product->getName().'</strong>');
                $approval = Approval::STATUS_APPROVED;
            } else {
                /*Send update product notification email*/
                $queue =  \Magento\Framework\App\ObjectManager::getInstance()
                    ->create('Vnecoms\VendorsProduct\Model\Queue');
                $queue->publish(
                    'reject_product',
                    null,
                    $vendor->getId(),
                    $product->getId()
                );
                ///$this->productHelper->sendProductUnapprovedEmailToVendor($product, $vendor);
                $message = __('Product %1 is rejected', '<strong>'.$product->getName().'</strong>');
            }
            $product->setApproval($approval)->getResource()->saveAttribute($product, 'approval');
            if($product->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE){
                $childProductCollection = $product->getTypeInstance()->getUsedProductCollection($product);
                foreach($childProductCollection as $childProduct){
                    $childProduct->setApproval($approval)
                        ->getResource()
                        ->saveAttribute($childProduct, 'approval');
                }
            }
            $productIds[] = $product->getId();
            $this->_eventManager->dispatch(
                'vnecoms_vendors_push_notification',
                [
                    'vendor_id' => $vendor->getId(),
                    'type' => 'product_approval',
                    'message' => $message,
                    'additional_info' => ['id' => $product->getId()],
                ]
            );

            $productApproved++;
        }

        if($productIds)
        $this->_reindexByProductsIds($productIds);
        $this->messageManager->addSuccess(
            __('A total of %1 product(s) have been unapproved.', $productApproved)
        );

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('vendors/catalog_product/index');
    }

    /**
     * @param $productIds
     * @param $indexLists
     */
    private function _reindexByProductsIds($productIds)
    {
        $indexLists = [
            'catalog_category_product',
            'catalog_product_category',
            'catalog_product_attribute',
            'catalog_product_price',
            'catalogsearch_fulltext',
            'cataloginventory_stock'
        ];

        foreach($indexLists as $indexList) {
            $categoryIndexer = $this->indexerRegistry->get($indexList);
            $categoryIndexer->reindexList(array_unique($productIds));
        }
    }
}
