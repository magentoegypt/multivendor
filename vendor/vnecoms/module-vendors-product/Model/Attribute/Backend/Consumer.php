<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Vnecoms\VendorsProduct\Model\Attribute\Backend;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\TemporaryStateExceptionInterface;
use Magento\Framework\Bulk\OperationInterface;

/**
 * Consumer for export message.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Consumer
{
    /**
     * @var \Vnecoms\VendorsProduct\Helper\Data
     */
    protected $process;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    private $serializer;

    /**
     * @var \Magento\Framework\Bulk\OperationManagementInterface
     */
    private $operationManagement;
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var \Magento\Framework\Indexer\IndexerRegistry
     */
    protected $indexerRegistry;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productFactory;

    /**
     * @var \Vnecoms\Vendors\Model\VendorFactory
     */
    protected $vendorFactory;

    /**
     * @var \Vnecoms\VendorsProduct\Model\ResourceModel\Product\Update\Collection
     */
    protected $updateCollection;

    /**
     * @var \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable
     */
    protected $_catalogProductTypeConfigurable;

    /**
     * Consumer constructor.
     * @param \Vnecoms\VendorsProduct\Helper\Data $process
     * @param \Magento\Framework\Indexer\IndexerRegistry $reIndex
     * @param \Magento\Framework\Bulk\OperationManagementInterface $operationManagement
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     * @param \Vnecoms\VendorsProduct\Model\ResourceModel\Product\Update\Collection $collection
     * @param \Vnecoms\Vendors\Model\VendorFactory $vendorFactory
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $catalogProductTypeConfigurable
     * @param EntityManager $entityManager
     */
    public function __construct(
        \Vnecoms\VendorsProduct\Helper\Data $process,
        \Magento\Framework\Indexer\IndexerRegistry $reIndex,
        \Magento\Framework\Bulk\OperationManagementInterface $operationManagement,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Vnecoms\VendorsProduct\Model\ResourceModel\Product\Update\Collection $collection,
        \Vnecoms\Vendors\Model\VendorFactory $vendorFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $catalogProductTypeConfigurable,
        EntityManager $entityManager
    ) {
        $this->indexerRegistry = $reIndex;
        $this->logger = $logger;
        $this->serializer = $serializer;
        $this->operationManagement = $operationManagement;
        $this->entityManager = $entityManager;
        $this->updateCollection = $collection;
        $this->productFactory = $productFactory;
        $this->vendorFactory = $vendorFactory;
        $this->process  = $process;
        $this->_catalogProductTypeConfigurable = $catalogProductTypeConfigurable;
    }

    /**
     * Process
     *
     * @param \Magento\AsynchronousOperations\Api\Data\OperationInterface $operation
     * @throws \Exception
     *
     * @return void
     */
    public function process(\Magento\AsynchronousOperations\Api\Data\OperationInterface $operation)
    {
        try {
            $serializedData = $operation->getSerializedData();
            $data = $this->serializer->unserialize($serializedData);
            $this->execute($data);
        } catch (\Zend_Db_Adapter_Exception $e) {
            $this->logger->critical($e->getMessage());
            if ($e instanceof \Magento\Framework\DB\Adapter\LockWaitException
                || $e instanceof \Magento\Framework\DB\Adapter\DeadlockException
                || $e instanceof \Magento\Framework\DB\Adapter\ConnectionException
            ) {
                $status = OperationInterface::STATUS_TYPE_RETRIABLY_FAILED;
                $errorCode = $e->getCode();
                $message = $e->getMessage();
            } else {
                $status = OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
                $errorCode = $e->getCode();
                $message = __(
                    'Sorry, something went wrong during send email. Please see log for details.'
                );
            }
        } catch (NoSuchEntityException $e) {
            $this->logger->critical($e->getMessage());
            $status = ($e instanceof TemporaryStateExceptionInterface)
                ? OperationInterface::STATUS_TYPE_RETRIABLY_FAILED
                : OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
            $errorCode = $e->getCode();
            $message = $e->getMessage();
        } catch (LocalizedException $e) {
            $this->logger->critical($e->getMessage());
            $status = OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
            $errorCode = $e->getCode();
            $message = $e->getMessage();
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $status = OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
            $errorCode = $e->getCode();
            $message = __('Sorry, something went wrong during send email. Please see log for details.');
        }

        $operation->setStatus($status ?? OperationInterface::STATUS_TYPE_COMPLETE)
            ->setErrorCode($errorCode ?? null)
            ->setResultMessage($message ?? null);

        $this->entityManager->save($operation);
    }

    /**
     * Execute
     *
     * @param array $data
     *
     * @return void
     */
    private function execute($data): void
    {
        $productIds = [];

        $vendor = $this->vendorFactory->create()->load($data['vendor_id']);
        if (!$vendor->getId()) return;

        $product = $this->productFactory->create()->getCollection()->addAttributeToSelect("*")
            ->addAttributeToFilter("entity_id", $data['product_id'])->getFirstItem();

        if (!$product->getId()) return;

        //process parent product
        $parentByChilds = $this->_catalogProductTypeConfigurable->getParentIdsByChild($product->getId());
        if ($parentByChilds) {
            $productIds = array_merge($productIds, $parentByChilds);
        }

        $productIds[] = $product->getId();
        $this->_reindexByProductsIds(array_unique($productIds));

        $updateCollection = false;
        if ($data['addition_data']) {
            $updateCollection = $this->updateCollection->addFieldToFilter("update_id",
                ['IN' =>  $data['addition_data']]);
        }
        try {
            switch ($data['type']) {
                case 'new_product':
                    $this->process->sendNewProductApprovalEmailToAdmin($product, $vendor);
                    break;
                case 'update_product':
                    $this->process->sendUpdateProductApprovalEmailToAdmin($product, $vendor);
                    break;
                case 'update_approval_product':
                    if ($updateCollection) {
                        $this->process->sendUpdateProductApprovedEmailToVendor($product, $vendor, $updateCollection);
                    } else {
                        $this->process->sendProductApprovedEmailToVendor($product, $vendor);
                    }
                    break;
                case 'approval_product':
                    $this->process->sendProductApprovedEmailToVendor($product, $vendor);
                    break;
                case 'update_reject_product':
                    if ($updateCollection) {
                        $this->process->sendUpdateProductUnapprovedEmailToVendor($product, $vendor, $updateCollection);
                    } else {
                        $this->process->sendProductUnapprovedEmailToVendor($product, $vendor);
                    }
                    break;
                case 'reject_product':
                    $this->process->sendProductUnapprovedEmailToVendor($product, $vendor);
                    break;
            }
        } catch (\Exception $e) {
        }

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
