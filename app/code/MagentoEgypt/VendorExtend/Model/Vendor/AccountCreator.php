<?php
declare(strict_types=1);

namespace MagentoEgypt\VendorExtend\Model\Vendor;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\CustomerGraphQl\Model\Customer\ValidateCustomerData;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Store\Model\Store;
use Vnecoms\VendorsApi\Model\Data\Vendor as VendorDataModel;
use Vnecoms\VendorsApi\Model\Data\Vendor\ToModel;

/**
 * Vnecoms' ToModel::createVendorAccount(), minus its debugging leftover.
 *
 * The original ends in `catch (LocalizedException $e) { var_dump($e->getMessage()); exit; }`,
 * so ANY validation failure during seller registration (duplicate email, weak password,
 * missing company...) answered HTTP 200 with a raw PHP dump instead of a REST error. The
 * steps below are the original's, in the same order; errors now propagate and the
 * repository turns them into a normal 400 with the message.
 */
class AccountCreator
{
    private CustomerInterfaceFactory $customerFactory;
    private AccountManagementInterface $accountManagement;
    private ValidateCustomerData $validateCustomerData;
    private DataObjectHelper $dataObjectHelper;
    private DataObjectProcessor $dataObjectProcessor;
    private \Vnecoms\Vendors\Helper\Data $vendorHelper;
    private \Vnecoms\Credit\Model\CreditFactory $creditFactory;
    private \Vnecoms\VendorsApi\Helper\Data $helperApi;

    public function __construct(
        CustomerInterfaceFactory $customerFactory,
        AccountManagementInterface $accountManagement,
        ValidateCustomerData $validateCustomerData,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        \Vnecoms\Vendors\Helper\Data $vendorHelper,
        \Vnecoms\Credit\Model\CreditFactory $creditFactory,
        \Vnecoms\VendorsApi\Helper\Data $helperApi
    ) {
        $this->customerFactory = $customerFactory;
        $this->accountManagement = $accountManagement;
        $this->validateCustomerData = $validateCustomerData;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->vendorHelper = $vendorHelper;
        $this->creditFactory = $creditFactory;
        $this->helperApi = $helperApi;
    }

    /**
     * @param ToModel $toModel the intercepted converter, so its own plugins still run on toModel()
     * @param VendorDataModel $dataModel
     * @param Store $store
     * @return \Vnecoms\VendorsApi\Api\Data\VendorInterface
     * @throws LocalizedException
     */
    public function create(ToModel $toModel, VendorDataModel $dataModel, Store $store)
    {
        $vendor = $toModel->toModel($dataModel);
        if (!$vendor->getId()) {
            if ($vendor->getCustomerId()) {
                $customer = $this->helperApi->getCustomer($vendor->getCustomerId());
                if (!$customer->getId()) {
                    throw new \Magento\Framework\Exception\NoSuchEntityException();
                }
            } else {
                $customer = $this->createCustomerAccount($vendor->getData(), $store);
            }
            $vendor->setCustomer($customer);
            $vendor->setWebsiteId($customer->getWebsiteId());
        } else {
            $customer = $vendor->getCustomer();
        }
        $vendor->save();

        $vendor->sendNewAccountEmail($this->vendorHelper->isRequiredVendorApproval() ? 'registered' : 'active');

        $this->creditFactory->create()->loadByCustomerId($customer->getId());

        return $toModel->getById($vendor->getId());
    }

    /**
     * Same as ToModel::createCustomerAccount() (private there).
     */
    private function createCustomerAccount(array $data, Store $store): CustomerInterface
    {
        $customerDataObject = $this->customerFactory->create();
        unset($data['extension_attributes']);

        $requiredDataAttributes = $this->dataObjectProcessor->buildOutputDataArray(
            $customerDataObject,
            CustomerInterface::class
        );
        $data = array_merge($requiredDataAttributes, $data);
        $this->validateCustomerData->execute($data);
        $this->dataObjectHelper->populateWithArray($customerDataObject, $data, CustomerInterface::class);

        $customerDataObject->setWebsiteId($store->getWebsiteId());
        $customerDataObject->setStoreId($store->getId());
        $password = array_key_exists('password', $data) ? $data['password'] : null;
        return $this->accountManagement->createAccount($customerDataObject, $password);
    }
}
