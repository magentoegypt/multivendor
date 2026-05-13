<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsApi\Model\Data\Vendor;

use Vnecoms\VendorsApi\Model\Data\Vendor as VendorDataModel;
use Vnecoms\Vendors\Model\Vendor;
use Magento\CustomerGraphQl\Model\Customer\ValidateCustomerData;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Store\Model\Store;

class ToModel
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
     * @var \Vnecoms\Vendors\Helper\Data
     */
    protected $_vendorHelper;

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
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * @var \Vnecoms\Credit\Model\CreditFactory
     */
    protected $creditAccount;

    /**
     * @var \Vnecoms\VendorsApi\Api\Data\VendorInterfaceFactory
     */
    private $vendorDataFactory;

    /**
     * Core event manager proxy
     *
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $_eventManager = null;

    /**
     * @var \Vnecoms\VendorsApi\Helper\Data
     */
    protected $helperApi;

    /**
     * ToModel constructor.
     * @param DataObjectHelper $dataObjectHelper
     * @param \Vnecoms\Vendors\Model\VendorFactory $vendorFactory
     * @param \Magento\Framework\Reflection\DataObjectProcessor $dataObjectProcessor
     * @param \Vnecoms\Vendors\Helper\Data $vendorHelper
     * @param CustomerInterfaceFactory $customerFactory
     * @param AccountManagementInterface $accountManagement
     * @param ValidateCustomerData $validateCustomerData
     * @param \Vnecoms\Credit\Model\CreditFactory $creditFactory
     * @param \Vnecoms\VendorsApi\Api\Data\VendorInterfaceFactory $vendorDataFactory
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Vnecoms\VendorsApi\Helper\Data $helperApi
     */
    public function __construct(
        DataObjectHelper $dataObjectHelper,
        \Vnecoms\Vendors\Model\VendorFactory $vendorFactory,
        \Magento\Framework\Reflection\DataObjectProcessor $dataObjectProcessor,
        \Vnecoms\Vendors\Helper\Data $vendorHelper,
        CustomerInterfaceFactory $customerFactory,
        AccountManagementInterface $accountManagement,
        ValidateCustomerData $validateCustomerData,
        \Vnecoms\Credit\Model\CreditFactory $creditFactory,
        \Vnecoms\VendorsApi\Api\Data\VendorInterfaceFactory $vendorDataFactory,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Vnecoms\VendorsApi\Helper\Data $helperApi
    ) {
        $this->_vendorHelper = $vendorHelper;
        $this->vendorFactory = $vendorFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->customerFactory = $customerFactory;
        $this->accountManagement = $accountManagement;
        $this->validateCustomerData = $validateCustomerData;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->creditAccount = $creditFactory;
        $this->vendorDataFactory = $vendorDataFactory;
        $this->_eventManager = $eventManager;
        $this->helperApi = $helperApi;
    }

    /**
     * @param $vendorId
     * @return mixed
     */
    public function getById($vendorId) {
        $vendorModel = $this->vendorFactory->create()->load($vendorId);
        $vendorData = $this->convertDataVendor($vendorModel, $vendorModel->getCustomer());
        $this->_eventManager->dispatch('api_vendor_load_after', ["vendor_data" => $vendorData, "vendor_model" => $vendorModel]);
        return $vendorData;
    }


    /**
     * @param $customerId
     * @return mixed
     * @throws \Magento\Framework\Exception\AuthorizationException
     */
    public function getCustomerById($customerId) {

        $customer   = $this->helperApi->getCustomer($customerId);
        $vendorModel     = $this->helperApi->getVendorByCustomer($customer);
        $vendorData = $this->convertDataVendor($vendorModel, $customer);
        $this->_eventManager->dispatch('api_vendor_load_after', ["vendor_data" => $vendorData, "vendor_model" => $vendorModel]);
        return $vendorData;
    }

    /**
     * @param VendorDataModel $dataModel
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\InputException
     */
    public function toModel(VendorDataModel $dataModel)
    {
        $vendorId = $dataModel->getId();
        $extension_attributes = $dataModel->getExtensionAttributes();
        if ($vendorId) {
            $vendorModel = $this->vendorFactory->create()->load($vendorId);
            if (!$vendorModel->getId()) {
                throw new \Magento\Framework\Exception\NoSuchEntityException();
            }
        } else {
            $vendorModel = $this->vendorFactory->create();
        }

        $modelData = $vendorModel->getData();

        $data = $this->dataObjectProcessor->buildOutputDataArray(
            $dataModel,
            \Vnecoms\VendorsApi\Api\Data\VendorInterface::class
        );

        if ($extension_attributes) {
            $extension_attributes = $extension_attributes->__toArray();
            foreach ($extension_attributes as $attributeCode => $value) {
                $data[$attributeCode] = $value;
            }
        }

        $mergedData = array_merge($modelData, $data);
        if (!isset($mergedData['group_id'])) {
            $mergedData['group_id'] = $this->_vendorHelper->getDefaultVendorGroup();
        }

        if (!isset($mergedData['status'])) {
            $mergedData['status'] = Vendor::STATUS_APPROVED;
        }

        if (!isset($mergedData['email'])) {
            $mergedData['email'] = isset($mergedData['vendor_id']) ? $mergedData['vendor_id']."@gmail.com" : time()."@gmail.com";
        }

        $vendorModel->setData($mergedData);
        $validateResult = $vendorModel->validate();
        if ($validateResult !== true) {
            $text = '';
            /** @var \Magento\Framework\Phrase $errorMessage */
            foreach ($validateResult as $errorMessage) {
                $text .= $errorMessage;
                $text .= '; ';
            }
            throw new \Magento\Framework\Exception\InputException(new \Magento\Framework\Phrase($text));
        }

        return $vendorModel;
    }

    /**
     * @param VendorDataModel $dataModel
     * @param Store $store
     * @return mixed
     */
    public function createVendorAccount(VendorDataModel $dataModel, Store $store)
    {
        try {
            $dataModel = $this->toModel($dataModel);
            if (!$dataModel->getId()){

                if ($dataModel->getCustomerId()) {
                    $customer   = $this->helperApi->getCustomer($dataModel->getCustomerId());
                    if (!$customer->getId()) {
                        throw new \Magento\Framework\Exception\NoSuchEntityException();
                    }
                } else {
                    $customer = $this->createCustomerAccount($dataModel->getData(), $store);
                }
                $dataModel->setCustomer($customer);
                $dataModel->setWebsiteId($customer->getWebsiteId());
            } else {
                $customer = $dataModel->getCustomer();
            }
            $dataModel->save();

            if ($this->_vendorHelper->isRequiredVendorApproval()) {
                $dataModel->sendNewAccountEmail("registered");
            } else {
                $dataModel->sendNewAccountEmail("active");
            }


            $this->creditAccount->create()->loadByCustomerId($customer->getId());
            $vendor = $this->convertDataVendor($dataModel, $customer);
        } catch (LocalizedException $e) {
            var_dump($e->getMessage());exit;
            throw new LocalizedException(__($e->getMessage()));
        }

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
            \Vnecoms\VendorsApi\Api\Data\VendorInterface::class
        );
        $vendorDataObject->setId($vendor->getId());
        $vendorDataObject->setCustomerId($customer->getId());
        $vendorDataObject->setEmail($customer->getEmail());
        $vendorDataObject->setGroupName($vendor->getGroup()->getVendorGroupCode());
        return $vendorDataObject;
    }

    /**
     * @param array $data
     * @param Store $store
     * @return CustomerInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\GraphQl\Exception\GraphQlInputException
     */
    private function createCustomerAccount(array $data, Store $store): CustomerInterface
    {
        $customerDataObject = $this->customerFactory->create();
        if (isset($data['extension_attributes']))
            unset($data['extension_attributes']);

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
}
