<?php

namespace Vnecoms\VendorsConfig\Model;

use Vnecoms\VendorsConfig\Api\Data\DocumentContentInterface;
use Vnecoms\VendorsConfig\Api\Data\DocumentContentInterfaceFactory;
use Vnecoms\VendorsConfig\Api\Data\DocumentProcessorInterface;
use Vnecoms\VendorsConfig\Model\ResourceModel\Config\CollectionFactory as ConfigCollectionFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\SortOrder;

/**
 * Vendor repository.
 */
class ConfigRepository implements \Vnecoms\VendorsConfig\Api\ConfigRepositoryInterface
{
    /**
     * @var \Vnecoms\Vendors\Model\VendorFactory
     */
    protected $vendorFactory;

    /**
     * @var \Magento\Framework\Reflection\DataObjectProcessor
     */
    protected $dataObjectProcessor;

    /**
     * @var \Vnecoms\VendorsConfig\Model\Config
     */
    protected $vendorConfig;

    /**
     * Backend Config Model Factory
     *
     * @var \Vnecoms\VendorsConfig\Helper\Data
     */
    protected $_configHelper;

    /**
     * @var \Vnecoms\VendorsConfig\Helper\Api
     */
    protected $apiHelper;

    /**
     * @var DocumentContentInterfaceFactory
     */
    protected $documentFactory;

    /**
     * @var DocumentProcessorInterface
     */
    protected $documentProcessor;

    /**
     * @var \Vnecoms\VendorsConfig\Api\Data\ConfigSearchResultInterfaceFactory
     */
    protected $searchResultsFactory;

    /**
     * @var ConfigCollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * ConfigRepository constructor.
     * @param \Vnecoms\Vendors\Model\VendorFactory $vendorFactory
     * @param \Vnecoms\VendorsConfig\Helper\Data $configHelper
     * @param \Magento\Framework\Reflection\DataObjectProcessor $dataObjectProcessor
     * @param ConfigFactory $vendorConfig
     * @param \Vnecoms\VendorsConfig\Helper\Api $apiHelper
     * @param DocumentProcessorInterface $documentProcessor
     * @param DocumentContentInterfaceFactory $documentFactory
     * @param \Vnecoms\VendorsConfig\Api\Data\ConfigSearchResultInterfaceFactory $searchResultsFactory
     * @param ConfigCollectionFactory $collectionFactory
     * @param DataObjectHelper $dataObjectHelper
     */
    public function __construct(
        \Vnecoms\Vendors\Model\VendorFactory $vendorFactory,
        \Vnecoms\VendorsConfig\Helper\Data $configHelper,
        \Magento\Framework\Reflection\DataObjectProcessor $dataObjectProcessor,
        \Vnecoms\VendorsConfig\Model\ConfigFactory $vendorConfig,
        \Vnecoms\VendorsConfig\Helper\Api $apiHelper,
        DocumentProcessorInterface $documentProcessor,
        DocumentContentInterfaceFactory $documentFactory,
        \Vnecoms\VendorsConfig\Api\Data\ConfigSearchResultInterfaceFactory $searchResultsFactory,
        ConfigCollectionFactory $collectionFactory,
        DataObjectHelper $dataObjectHelper
    ) {
        $this->vendorFactory               = $vendorFactory;
        $this->_configHelper = $configHelper;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->vendorConfig = $vendorConfig;
        $this->apiHelper = $apiHelper;
        $this->documentFactory                  = $documentFactory;
        $this->documentProcessor                = $documentProcessor;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionFactory = $collectionFactory;
        $this->dataObjectHelper = $dataObjectHelper;
    }

    /**
     * admin save config
     *
     * @param string $vendorId
     * @param string $path
     * @param string $value
     * @param int $storeId
     * @return \Vnecoms\VendorsConfig\Api\Data\ConfigInterface
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\StateException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function saveByVendorId($vendorId, $path, $value, $storeId = 0)
    {
        $vendor = $this->vendorFactory->create()->load($vendorId);

        if (!$vendor->getId()) {
            throw \Magento\Framework\Exception\NoSuchEntityException::singleField('vendor_id', "Vendor do not exist");
        }

        $config = $this->vendorConfig->create()->getCollection()->addFieldToFilter("vendor_id", $vendor->getId())
            ->addFieldToFilter("path", $path)
            ->addFieldToFilter("store_id", $storeId)->getFirstItem();
        $value = $this->getValueFieldConfig($path, $value);
        if ($config->getId()) {
            $config->setValue($value)->save();
        } else {
            $config = $this->vendorConfig->create()->setData([
                "vendor_id" => $vendor->getId(),
                "path" => $path,
                "value" => $value,
                "store_id" => $storeId
            ])->save();
        }
        return $config;
    }

    /**
     * @param int $customerId
     * @param \Magento\Framework\Api\SearchCriteriaInterface $criteria
     * @return \Vnecoms\VendorsApi\Api\Data\NotificationSearchResultInterface
     */
    public function getList(
        $customerId,
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $customer   = $this->apiHelper->getCustomer($customerId);
        $vendor     = $this->apiHelper->getVendorByCustomer($customer);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);

        $collection = $this->collectionFactory->create();
        foreach ($criteria->getFilterGroups() as $filterGroup) {
            foreach ($filterGroup->getFilters() as $filter) {
                $condition = $filter->getConditionType() ?: 'eq';
                $collection->addFieldToFilter($filter->getField(), [$condition => $filter->getValue()]);
            }
        }

        $collection->addFieldToFilter("vendor_id", $vendor->getId());

        $searchResults->setTotalCount($collection->getSize());
        $sortOrders = $criteria->getSortOrders();
        if ($sortOrders) {
            /** @var SortOrder $sortOrder */
            foreach ($sortOrders as $sortOrder) {
                $collection->addOrder(
                    $sortOrder->getField(),
                    ($sortOrder->getDirection() == SortOrder::SORT_ASC) ? 'ASC' : 'DESC'
                );
            }
        }
        $collection->setCurPage($criteria->getCurrentPage());
        $collection->setPageSize($criteria->getPageSize());
        $actions = [];

        /** @var Page $model */
        foreach ($collection as $model) {
            $config = $this->vendorConfig->create();

            $this->dataObjectHelper->populateWithArray(
                $config,
                $model->getData(),
                'Vnecoms\VendorsConfig\Api\Data\ConfigInterface'
            );

            $actions[] = $this->dataObjectProcessor->buildOutputDataArray(
                $config,
                'Vnecoms\VendorsConfig\Api\Data\ConfigInterface'
            );
        }
        $searchResults->setItems($actions);
        return $searchResults;
    }
    
    /**
     * admin save config
     *
     * @param string $customerId
     * @param string $path
     * @param string $value
     * @param int $storeId
     * @return \Vnecoms\VendorsConfig\Api\Data\ConfigInterface
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\StateException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function saveByCustomerId($customerId, $path, $value, $storeId = 0)
    {
        $customer   = $this->apiHelper->getCustomer($customerId);
        $vendor     = $this->apiHelper->getVendorByCustomer($customer);

        if (!$vendor->getId()) {
            throw \Magento\Framework\Exception\NoSuchEntityException::singleField('vendor_id', "Vendor do not exist");
        }

        $config = $this->vendorConfig->create()->getCollection()->addFieldToFilter("vendor_id", $vendor->getId())
            ->addFieldToFilter("path", $path)
            ->addFieldToFilter("store_id", $storeId)->getFirstItem();
        $value = $this->getValueFieldConfig($path, $value);
        if ($config->getId()) {
            $config->setValue($value)->save();
        } else {
            $config = $this->vendorConfig->create()->setData([
                "vendor_id" => $vendor->getId(),
                "path" => $path,
                "value" => $value,
                "store_id" => $storeId
            ])->save();
        }
        return $config;
    }


    /**
     * @param $customer
     * @param $avatar
     *
     * @return array
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getValueFieldConfig($path, $value)
    {
        $pathUpload = $this->_getUploadDir($path);

        if (is_array($value) && !$pathUpload) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('The value field is wrong format.')
            );
        }

        if ($pathUpload) {
            if (!isset($value['name']) || !isset($value['base64_encoded_data']) || !isset($value['type'])) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('The content file is wrong.')
                );
            }
            /** @var DocumentContentInterface $contentDataObject */
            $contentDataObject = $this->documentFactory
                ->create()
                ->setName($value['name'])
                ->setBase64EncodedData($value['base64_encoded_data'])
                ->setType($value['type']);
            $relativeFilePath  = $this->documentProcessor->processDocumentContent($pathUpload, $contentDataObject);
            if ($relativeFilePath) {
                $relativeFilePath = ltrim($relativeFilePath, "/");
                $value  = $relativeFilePath;
            } else {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Cant not upload file. Please try again')
                );
            }
        }

        return $value;
    }

    /**
     * Return path to directory for upload file
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _getUploadDir($fieldPath)
    {
        $fields = $this->apiHelper->getFieldConfigFile();

        if (!isset($fields[$fieldPath])) {
            return false;
        }
        return $fields[$fieldPath];
    }

}
