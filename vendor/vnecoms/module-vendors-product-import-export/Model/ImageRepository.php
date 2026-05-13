<?php

namespace Vnecoms\VendorsProductImportExport\Model;

use Vnecoms\VendorsProductImportExport\Api\Data\DocumentContentInterface;
use Vnecoms\VendorsProductImportExport\Api\Data\DocumentContentInterfaceFactory;
use Vnecoms\VendorsProductImportExport\Api\Data\DocumentProcessorInterface;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Vendor repository.
 */
class ImageRepository implements \Vnecoms\VendorsProductImportExport\Api\ImageRepositoryInterface
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
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var Data\ImageFactory
     */
    protected $imageFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_localeDate;

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $filesystem;

    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    protected $_file;

    /**
     * @var \Magento\Framework\Event\ManagerInterface 
     */
    protected $_eventManager;

    /**
     * ImageRepository constructor.
     * @param \Vnecoms\Vendors\Model\VendorFactory $vendorFactory
     * @param \Magento\Framework\Reflection\DataObjectProcessor $dataObjectProcessor
     * @param Data\ImageFactory $imageFactory
     * @param \Vnecoms\VendorsProductImportExport\Helper\Api $apiHelper
     * @param DocumentProcessorInterface $documentProcessor
     * @param DocumentContentInterfaceFactory $documentFactory
     * @param \Vnecoms\VendorsProductImportExport\Api\Data\ImageSearchResultInterfaceFactory $searchResultsFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param DataObjectHelper $dataObjectHelper
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\Filesystem\Driver\File $file
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     */
    public function __construct(
        \Vnecoms\Vendors\Model\VendorFactory $vendorFactory,
        \Magento\Framework\Reflection\DataObjectProcessor $dataObjectProcessor,
        \Vnecoms\VendorsProductImportExport\Model\Data\ImageFactory $imageFactory,
        \Vnecoms\VendorsProductImportExport\Helper\Api $apiHelper,
        DocumentProcessorInterface $documentProcessor,
        DocumentContentInterfaceFactory $documentFactory,
        \Vnecoms\VendorsProductImportExport\Api\Data\ImageSearchResultInterfaceFactory $searchResultsFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        DataObjectHelper $dataObjectHelper,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Filesystem\Driver\File $file,
        \Magento\Framework\Event\ManagerInterface $eventManager
    ) {
        $this->vendorFactory               = $vendorFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->imageFactory = $imageFactory;
        $this->apiHelper = $apiHelper;
        $this->storeManager = $storeManager;
        $this->documentFactory                  = $documentFactory;
        $this->documentProcessor                = $documentProcessor;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->_localeDate = $localeDate;
        $this->filesystem = $filesystem;
        $this->_file = $file;
        $this->_eventManager = $eventManager;
    }

    /**
     * @param int $customerId
     * @return \Vnecoms\VendorsApi\Api\Data\NotificationSearchResultInterface
     */
    public function getList(
        $customerId
    ) {
        $customer   = $this->apiHelper->getCustomer($customerId);
        $vendor     = $this->apiHelper->getVendorByCustomer($customer);

        if (!$vendor->getId()) {
            throw \Magento\Framework\Exception\NoSuchEntityException::singleField('vendor_id', "Vendor do not exist");
        }
        
        $searchResults = $this->searchResultsFactory->create();
       // $searchResults->setSearchCriteria($criteria);

        $mediaDirectory = $this->filesystem
            ->getDirectoryRead(DirectoryList::MEDIA);

        $path = 'vnecoms_import/'.$vendor->getVendorId();
        $destinationFolder = $mediaDirectory->getAbsolutePath($path);
        $this->_createDestinationFolder($destinationFolder);
        $dir = new \DirectoryIterator($destinationFolder);
        $images = [];

        foreach ($dir as $fileinfo) {
            if (!$fileinfo->isDot()) {
                $fileName = $fileinfo->getFilename();
                $fileName = ltrim($fileName, "/");
                $tmpImage = [
                    'name' => $fileName,
                    'file' => $fileName,
                    'size' => $fileinfo->getSize(),
                    'type' => $fileinfo->getType(),
                    'url' => $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . $path.'/' . $fileName,
                    'last_modify' => date('Y-m-d h:i:s', $fileinfo->getMTime()),
                ];
                
                $image = $this->imageFactory->create();

                $this->dataObjectHelper->populateWithArray(
                    $image,
                    $tmpImage,
                    'Vnecoms\VendorsProductImportExport\Api\Data\ImageInterface'
                );

                $images[] = $this->dataObjectProcessor->buildOutputDataArray(
                    $image,
                    'Vnecoms\VendorsProductImportExport\Api\Data\ImageInterface'
                );

            }
        }

        $searchResults->setTotalCount(count($images));
        $searchResults->setItems($images);
        return $searchResults;
    }

    /*
     *
     */
    private function _createDestinationFolder($destinationFolder)
    {
        if (!$destinationFolder) {
            return $this;
        }

        if (substr($destinationFolder, -1) == '/') {
            $destinationFolder = substr($destinationFolder, 0, -1);
        }

        if (!(@is_dir($destinationFolder)
            || @mkdir($destinationFolder, 0777, true)
        )) {
            throw new \Exception("Unable to create directory '{$destinationFolder}'.");
        }
        return $this;
    }

    /**
     * @param $customerId
     * @param $image
     * @return mixed
     * @throws CouldNotSaveException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function save($customerId, $image)
    {
        $customer   = $this->apiHelper->getCustomer($customerId);
        $vendor     = $this->apiHelper->getVendorByCustomer($customer);

        if (!$vendor->getId()) {
            throw \Magento\Framework\Exception\NoSuchEntityException::singleField('vendor_id', "Vendor do not exist");
        }

        $pathUpload = 'vnecoms_import/'.$vendor->getVendorId();

        try {
            /** @var DocumentContentInterface $contentDataObject */
            $contentDataObject = $this->documentFactory
                ->create()
                ->setName($image->getName())
                ->setBase64EncodedData($image->getBase64EncodedData())
                ->setType($image->getType());
            $result  = $this->documentProcessor->processDocumentContent($pathUpload, $contentDataObject);
            $fileName = ltrim($result['file'], "/");
            $result['url'] = $this->storeManager->getStore()
                    ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . $pathUpload.'/' .$fileName;
            $result['last_modify'] =  date("Y-m-d H:i:s", filemtime($result['path']));

            $image = $this->imageFactory->create();

            $this->dataObjectHelper->populateWithArray(
                $image,
                $result,
                'Vnecoms\VendorsProductImportExport\Api\Data\ImageInterface'
            );

        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }

        return $image;
    }

    /**
     * @param $customerId
     * @param $fileNames
     * @return mixed
     */
    public function deleteByFiles($customerId, $deletedFiles)
    {
        $customer   = $this->apiHelper->getCustomer($customerId);
        $vendorModel     = $this->apiHelper->getVendorByCustomer($customer);

        if (!$customer->getId()) {
            throw \Magento\Framework\Exception\NoSuchEntityException::singleField('vendor_id', "Vendor do not exist");
        }

        try {
            if (!$deletedFiles) {
                throw new \Exception(__("There is no data to process"));
            }
            $mediaDirectory = $this->filesystem
                ->getDirectoryRead(DirectoryList::MEDIA);

            $path = 'vnecoms_import/'.$vendorModel->getVendorId();
            foreach ($deletedFiles as $file) {
                $fileName = $mediaDirectory->getAbsolutePath($path.'/'.$file);

                $this->_eventManager->dispatch(
                    'vendor_import_image_delete_before',
                    ["file_name" => $path.'/'.$file]
                );

                $this->_file->deleteFile($fileName);
            }
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__($e->getMessage()));
        }
        return true;
    }
}
