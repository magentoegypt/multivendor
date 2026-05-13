<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Vnecoms\VendorsGraphQl\Model\Vendor;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Store\Api\Data\StoreInterface;
use Vnecoms\Vendors\Model\Vendor;
use Magento\CustomerGraphQl\Model\Customer\ValidateCustomerData;
use Vnecoms\VendorsApi\Api\VendorRepositoryInterface;
use Vnecoms\VendorsApi\Api\Data\VendorInterface;

/**
 * Create new customer account
 */
class CreateVendorAccount
{
    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * @var CustomerInterfaceFactory
     */
    private $customerFactory;

    /**
     * @var AccountManagementInterface
     */
    private $accountManagement;

    /**
     * @var ValidateCustomerData
     */
    private $validateCustomerData;

    /**
     * @var DataObjectProcessor
     */
    private $dataObjectProcessor;

    /**
     * @var \Vnecoms\Vendors\Helper\Data
     */
    protected $_vendorHelper;

    /**
     * @var \Vnecoms\Vendors\Model\VendorFactory
     */
    protected $_vendorFactory;

    /**
     * @var \Vnecoms\VendorsApi\Api\Data\VendorInterfaceFactory
     */
    private $vendorDataFactory;

    /**
     * @var \Vnecoms\Credit\Model\CreditFactory
     */
    protected $creditAccount;

    /**
     * CreateVendorAccount constructor.
     * @param DataObjectHelper $dataObjectHelper
     * @param CustomerInterfaceFactory $customerFactory
     * @param AccountManagementInterface $accountManagement
     * @param DataObjectProcessor $dataObjectProcessor
     * @param ValidateCustomerData $validateCustomerData
     * @param \Vnecoms\Vendors\Helper\Data $vendorHelper
     * @param \Vnecoms\Vendors\Model\VendorFactory $vendorFactory
     * @param \Vnecoms\VendorsApi\Api\Data\VendorInterfaceFactory $vendorDataFactory
     * @param \Vnecoms\Credit\Model\CreditFactory $creditAccountFactory
     */
    public function __construct(
        DataObjectHelper $dataObjectHelper,
        CustomerInterfaceFactory $customerFactory,
        AccountManagementInterface $accountManagement,
        DataObjectProcessor $dataObjectProcessor,
        ValidateCustomerData $validateCustomerData,
        \Vnecoms\Vendors\Helper\Data $vendorHelper,
        \Vnecoms\Vendors\Model\VendorFactory $vendorFactory,
        \Vnecoms\VendorsApi\Api\Data\VendorInterfaceFactory $vendorDataFactory,
        \Vnecoms\Credit\Model\CreditFactory $creditAccountFactory
    ) {
        $this->dataObjectHelper = $dataObjectHelper;
        $this->customerFactory = $customerFactory;
        $this->accountManagement = $accountManagement;
        $this->validateCustomerData = $validateCustomerData;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->_vendorHelper = $vendorHelper;
        $this->_vendorFactory = $vendorFactory;
        $this->vendorDataFactory = $vendorDataFactory;
        $this->creditAccount = $creditAccountFactory;

    }

    /**
     * @param array $data
     * @param StoreInterface $store
     * @return mixed
     * @throws GraphQlInputException
     */
    public function execute(array $data, StoreInterface $store)
    {
        try {
            if (!isset($data['vendor_id'])) {
                throw new LocalizedException(__('vendor_id value should be specified'));
            }
            $isCheckVendorId = $this->validateVendorId($data['vendor_id']);
            if ($isCheckVendorId) {
                throw new LocalizedException(__('Vendor id is already in used'));
            }
            $customer = $this->createAccount($data, $store);
            $vendor = $this->createVendor($data, $customer);
        } catch (LocalizedException $e) {
            throw new GraphQlInputException(__($e->getMessage()));
        }

        return $vendor;
    }

    /**
     * Create account
     *
     * @param array $data
     * @param StoreInterface $store
     * @return CustomerInterface
     * @throws LocalizedException
     */
    private function createAccount(array $data, StoreInterface $store): CustomerInterface
    {
        $customerDataObject = $this->customerFactory->create();
        /**
         * Add required attributes for customer entity
         */
        $requiredDataAttributes = $this->dataObjectProcessor->buildOutputDataArray(
            $customerDataObject,
            CustomerInterface::class
        );
        $data = array_merge($requiredDataAttributes, $data);
        $this->validateCustomerData->execute($data);
        $this->dataObjectHelper->populateWithArray(
            $customerDataObject,
            $data,
            CustomerInterface::class
        );
        $customerDataObject->setWebsiteId($store->getWebsiteId());
        $customerDataObject->setStoreId($store->getId());

        $password = array_key_exists('password', $data) ? $data['password'] : null;
        return $this->accountManagement->createAccount($customerDataObject, $password);
    }

    /**
     * @param $data
     * @param $customer
     * @return mixed
     * @throws \Exception
     */
    private function createVendor($data, $customer)
    {
        $vendor = $this->_vendorFactory->create();
        $vendor->setData($data);
        $vendor->setGroupId($this->_vendorHelper->getDefaultVendorGroup());
        $vendor->setCustomer($customer);
        $vendor->setWebsiteId($customer->getWebsiteId());

        if ($this->_vendorHelper->isRequiredVendorApproval()) {
            $vendor->setStatus(Vendor::STATUS_PENDING);
        } else {
            $vendor->setStatus(Vendor::STATUS_APPROVED);
        }
        $errors = $vendor->validate();

        if ($errors !== true) {
            throw new \Exception(implode("<br />", $errors));
        }

        $vendor->save();

        if ($this->_vendorHelper->isRequiredVendorApproval()) {
            $vendor->sendNewAccountEmail("registered");
        } else {
            $vendor->sendNewAccountEmail("active");
        }
        $this->creditAccount->create()->loadByCustomerId($customer->getId());

        $vendor = $this->convertDataVendor($vendor, $customer);
        return $vendor;
    }


    /**
     * @param $vendor
     * @param $customer
     * @return mixed
     */
    private function convertDataVendor($vendor, $customer) {
        $vendorDataObject = $this->vendorDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $vendorDataObject,
            $vendor->getData(),
            CustomerInterface::class
        );

        $vendorDataObject->setCustomerId($customer->getId());
        $vendorDataObject->setEmail($customer->getEmail());
        $vendorDataObject->setGroupName($vendor->getGroup()->getVendorGroupCode());
        return $vendorDataObject;
    }

    /**
     * @param $vendorId
     * @return bool
     * @throws \Exception
     */
    private function validateVendorId($vendorId)
    {
        try{
            $resource = $this->_vendorFactory->create()->getResource();
            $connection = $resource->getConnection();
            $select = $connection->select();
            $select->from(
                $resource->getTable('ves_vendor_entity'),
                'entity_id'
            )->where(
                'vendor_id = :vendor_id'
            );
            $bind = [
                'vendor_id' => $vendorId,
            ];

            $vendorId = $connection->fetchOne($select,$bind);
        }catch(\Exception $e){
            throw new \Exception($e->getMessage());
        }
        return $vendorId ? true : false;
    }
}
