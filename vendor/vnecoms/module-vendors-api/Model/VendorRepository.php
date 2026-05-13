<?php

namespace Vnecoms\VendorsApi\Model;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;

/**
 * Vendor repository.
 */
class VendorRepository implements \Vnecoms\VendorsApi\Api\VendorRepositoryInterface
{
    /**
     * @var \Vnecoms\VendorsApi\Helper\Data
     */
    protected $helper;

    /**
     * @var \Vnecoms\VendorsApi\Api\Data\VendorInterfaceFactory
     */
    protected $vendorDataFactory;

    /**
     * @var \Magento\Framework\Api\DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var \Vnecoms\VendorsDashboard\Model\Graph
     */
    protected $graph;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Data\Vendor\ToModel
     */
    protected $toModelConvert;

    /**
     * VendorRepository constructor.
     * @param \Vnecoms\VendorsApi\Helper\Data $helper
     * @param \Vnecoms\VendorsApi\Api\Data\VendorInterfaceFactory $vendorDataFactory
     * @param \Magento\Framework\Api\DataObjectHelper $dataObjectHelper
     * @param \Vnecoms\VendorsDashboard\Model\Graph $graph
     * @param \Vnecoms\VendorsGraphQl\Model\Vendor\CreateVendorAccount $createVendorAccount
     * @param StoreManagerInterface $storeManager
     * @param Data\Vendor\ToModel $toModelConvert
     */
    public function __construct(
        \Vnecoms\VendorsApi\Helper\Data $helper,
        \Vnecoms\VendorsApi\Api\Data\VendorInterfaceFactory $vendorDataFactory,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
        \Vnecoms\VendorsDashboard\Model\Graph $graph,
        StoreManagerInterface $storeManager,
        \Vnecoms\VendorsApi\Model\Data\Vendor\ToModel $toModelConvert
    ) {
        $this->helper               = $helper;
        $this->vendorDataFactory    = $vendorDataFactory;
        $this->dataObjectHelper     = $dataObjectHelper;
        $this->graph                = $graph;
        $this->storeManager = $storeManager;
        $this->toModelConvert = $toModelConvert;
    }

    /**
     * {@inheritdoc}
     */
    public function getById($customerId)
    {
        try {
            $object =  $this->toModelConvert->getCustomerById($customerId);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                __('Could not save the page: %1', __('Something went wrong while get the vendor.')),
                $exception
            );
        }
        return $object;
    }


    /**
     * {@inheritdoc}
     */
    public function getByVendorId($vendorId)
    {
        try {
            $object =  $this->toModelConvert->getById($vendorId);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                __('Could not save the page: %1', __('Something went wrong while get the vendor.')),
                $exception
            );
        }
        return $object;
    }

    /**
     * @param \Vnecoms\VendorsApi\Api\Data\VendorInterface $vendor
     * @return mixed
     */
    public function save(\Vnecoms\VendorsApi\Api\Data\VendorInterface $vendor)
    {
        try {
            $store = $this->storeManager->getStore();
            $object =  $this->toModelConvert->createVendorAccount($vendor, $store);
        } catch (LocalizedException $exception) {
            throw new CouldNotSaveException(
                __('Could not save the vendor: %1', $exception->getMessage()),
                $exception
            );
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                __('Could not save the page: %1', __('Something went wrong while saving the vendor.')),
                $exception
            );
        }
        return $this->getByVendorId($object->getId());
    }

    /**
     * Delete vendor by Vendor ID.
     *
     * @param int $vendorId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($vendorId)
    {
        $customerModel = $this->toModelConvert->getById($vendorId);
        $customerModel->delete();
        return true;
    }

}
