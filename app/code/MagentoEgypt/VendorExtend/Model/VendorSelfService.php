<?php
declare(strict_types=1);

namespace MagentoEgypt\VendorExtend\Model;

use Magento\Catalog\Api\CategoryManagementInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Store\Model\StoreManagerInterface;
use MagentoEgypt\SmsExtend\Helper\Otp;
use MagentoEgypt\VendorExtend\Api\VendorSelfServiceInterface;
use MagentoEgypt\VendorExtend\Plugin\VendorsApi\VendorAccessGuard;
use Vnecoms\VendorsApi\Api\Data\VendorInterface;
use Vnecoms\VendorsApi\Model\Data\Vendor\ToModel;

/**
 * Seller self-service for the vendor app — see VendorSelfServiceInterface.
 */
class VendorSelfService implements VendorSelfServiceInterface
{
    /**
     * Profile fields a seller may change on their own account. Everything else in the
     * payload (id, vendor_id, status, group_id, customer_id, email, credit...) is dropped.
     */
    private const EDITABLE_FIELDS = [
        'company', 'telephone', 'street', 'city', 'postcode', 'region', 'region_id', 'country_id', 'fax',
    ];

    /** Extension attributes that are never taken from a self-service payload. */
    private const PROTECTED_EXTENSION_FIELDS = [
        'password', 'mobilenumber', 'status', 'group_id', 'vendor_id', 'customer_id', 'website_id',
        'entity_id', 'email', 'sms_credit', 'credit',
    ];

    private VendorAccessGuard $guard;
    private ToModel $toModel;
    private DataObjectProcessor $dataObjectProcessor;
    private CustomerRepositoryInterface $customerRepository;
    private ProductRepositoryInterface $productRepository;
    private StockRegistryInterface $stockRegistry;
    private CategoryManagementInterface $categoryManagement;
    private StoreManagerInterface $storeManager;
    private ExtensionAttributesFactory $extensionAttributesFactory;
    private Otp $otp;

    public function __construct(
        VendorAccessGuard $guard,
        ToModel $toModel,
        DataObjectProcessor $dataObjectProcessor,
        CustomerRepositoryInterface $customerRepository,
        ProductRepositoryInterface $productRepository,
        StockRegistryInterface $stockRegistry,
        CategoryManagementInterface $categoryManagement,
        StoreManagerInterface $storeManager,
        ExtensionAttributesFactory $extensionAttributesFactory,
        Otp $otp
    ) {
        $this->guard = $guard;
        $this->toModel = $toModel;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->customerRepository = $customerRepository;
        $this->productRepository = $productRepository;
        $this->stockRegistry = $stockRegistry;
        $this->categoryManagement = $categoryManagement;
        $this->storeManager = $storeManager;
        $this->extensionAttributesFactory = $extensionAttributesFactory;
        $this->otp = $otp;
    }

    /**
     * @inheritdoc
     */
    public function updateMe($customerId, VendorInterface $vendor)
    {
        $vendorModel = $this->guard->assertApprovedSeller((int) $customerId);

        $payload = $this->dataObjectProcessor->buildOutputDataArray($vendor, VendorInterface::class);
        $changes = array_intersect_key($payload, array_flip(self::EDITABLE_FIELDS));

        $extension = $vendor->getExtensionAttributes();
        if ($extension) {
            foreach ($extension->__toArray() as $code => $value) {
                if ($value !== null && !in_array($code, self::PROTECTED_EXTENSION_FIELDS, true)) {
                    $changes[$code] = $value;
                }
            }
        }

        if ($changes) {
            $vendorModel->addData($changes);
            $validation = $vendorModel->validate();
            if ($validation !== true) {
                throw new InputException(__(implode('; ', array_map('strval', (array) $validation))));
            }
            $vendorModel->save();
        }

        /* The seller's name lives on the customer, as the web profile form does it. */
        $this->updateCustomerName((int) $customerId, $vendor);

        return $this->toModel->getById($vendorModel->getId());
    }

    /**
     * @inheritdoc
     */
    public function deleteMe($customerId)
    {
        /* Any status: a pending or disabled seller must still be able to delete their account. */
        $vendorModel = $this->guard->loadByCustomerId((int) $customerId);
        if (!$vendorModel->getId()) {
            throw new NoSuchEntityException(__('This account is not registered as a seller.'));
        }
        /* Same effect as the admin route DELETE /V1/vendor/:vendorId the app used before. */
        $vendorModel->delete();
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getStockItem($customerId, $sku)
    {
        $vendorId = (int) $this->guard->assertApprovedSeller((int) $customerId)->getId();
        $notFound = new NoSuchEntityException(__('The product "%1" was not found among your products.', $sku));
        try {
            $product = $this->productRepository->get($sku);
        } catch (NoSuchEntityException $e) {
            throw $notFound;
        }
        /* Another seller's SKU answers exactly like a missing one. */
        if ((int) $product->getVendorId() !== $vendorId) {
            throw $notFound;
        }
        return $this->stockRegistry->getStockItemBySku($sku);
    }

    /**
     * @inheritdoc
     */
    public function getCategories($customerId, $rootCategoryId = null, $depth = null)
    {
        $this->guard->assertApprovedSeller((int) $customerId);
        if (!$rootCategoryId) {
            $rootCategoryId = (int) $this->storeManager->getStore()->getRootCategoryId();
        }
        return $this->categoryManagement->getTree((int) $rootCategoryId, $depth !== null ? (int) $depth : null);
    }

    /**
     * @inheritdoc
     */
    public function register(VendorInterface $vendor, $password, $registrationToken)
    {
        $mobile = $this->otp->peekRegistrationTicket((string) $registrationToken);
        if ($mobile === null) {
            throw new InputException(
                __('The registration token is invalid or has expired. Please verify your mobile number again.')
            );
        }
        if ($this->otp->getCustomersByMobile($mobile)) {
            throw new InputException(__('Mobile number already exists.'));
        }
        if (trim((string) $password) === '') {
            throw new InputException(__('Password is required.'));
        }

        /* An anonymous caller never chooses which record this is, nor its status. */
        $vendor->setId(null);
        $vendor->setStatus(null);
        $vendor->setGroupId(null);
        $vendor->setCustomerId(null);
        $vendor->setTelephone($mobile);

        $extension = $vendor->getExtensionAttributes()
            ?: $this->extensionAttributesFactory->create(\Vnecoms\VendorsApi\Model\Data\Vendor::class);
        $extension->setPassword((string) $password);
        $vendor->setExtensionAttributes($extension);

        try {
            $result = $this->toModel->createVendorAccount($vendor, $this->storeManager->getStore());
        } catch (LocalizedException $e) {
            throw new LocalizedException(__('Could not save the vendor: %1', $e->getMessage()), $e);
        }

        $this->otp->consumeRegistrationTicket((string) $registrationToken);
        return $result;
    }

    private function updateCustomerName(int $customerId, VendorInterface $vendor): void
    {
        $first = $vendor->getFirstname();
        $last = $vendor->getLastname();
        if ($first === null && $last === null) {
            return;
        }
        $customer = $this->customerRepository->getById($customerId);
        $changed = false;
        if ($first !== null && trim((string) $first) !== '' && $first !== $customer->getFirstname()) {
            $customer->setFirstname($first);
            $changed = true;
        }
        if ($last !== null && trim((string) $last) !== '' && $last !== $customer->getLastname()) {
            $customer->setLastname($last);
            $changed = true;
        }
        if ($changed) {
            $this->customerRepository->save($customer);
        }
    }
}
