<?php
declare(strict_types=1);

namespace MagentoEgypt\VendorExtend\Model;

use Magento\Catalog\Api\CategoryManagementInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject;
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
    private RegionFactory $regionFactory;

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
        Otp $otp,
        ?RegionFactory $regionFactory = null
    ) {
        /* Optional with a fallback so the compiled DI config keeps working until the next di:compile. */
        $this->regionFactory = $regionFactory ?: ObjectManager::getInstance()->get(RegionFactory::class);
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
            $changes = $this->normalizeRegion($vendorModel, $changes);
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
     * Seller product delete, scoped to the caller.
     *
     * Vnecoms routed DELETE /V1/vendors/product/:sku straight to core
     * Magento\Catalog\Api\ProductRepositoryInterface::deleteById($sku) and forced a customerId
     * the method does not take, so it answered 500 for every seller. Had that been "fixed" by
     * dropping the parameter, any seller could have deleted any product. This route (redefined
     * in etc/webapi.xml) deletes only a product the approved caller owns. Another seller's SKU
     * answers exactly like a missing one.
     */
    public function deleteProduct($customerId, $sku)
    {
        $vendorId = (int) $this->guard->assertApprovedSeller((int) $customerId)->getId();
        $notFound = new NoSuchEntityException(__('The product "%1" was not found among your products.', $sku));
        try {
            $product = $this->productRepository->get($sku);
        } catch (NoSuchEntityException $e) {
            throw $notFound;
        }
        if ((int) $product->getVendorId() !== $vendorId) {
            throw $notFound;
        }

        return $this->productRepository->delete($product);
    }

    /**
     * Sliding seller session for the app (TC70 14zb93nv65d).
     *
     * Seller tokens are Magento JWTs that expire 60 minutes after issue
     * (webapi/jwtauth/customer_expiration), and sellers sign in with a WhatsApp OTP, so the app
     * cannot sign in again silently: sellers were logged out every hour mid-session. The app calls
     * this with its current, still-valid token while in use and gets a fresh 60-minute token. An
     * idle seller still expires. An expired or revoked token never gets here: Web API
     * authentication answers 401 first.
     *
     * Magento revokes JWTs per user, not per token: it records a cut-off, and every token that
     * user was issued at or before it is refused. The cut-off is written for one second ago, then
     * the new token is issued, so the new token is newer than the cut-off and the old one is dead.
     * Side effect by design: every other token of that seller (another phone) is revoked too.
     */
    public function refreshToken($customerId)
    {
        $customerId = (int) $customerId;
        $this->guard->assertApprovedSeller($customerId);   // pending / disabled / expired: the usual 403
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $objectManager->get(\Magento\JwtUserToken\Api\RevokedRepositoryInterface::class)->saveRevoked(
            new \Magento\JwtUserToken\Api\Data\Revoked(
                \Magento\Authorization\Model\UserContextInterface::USER_TYPE_CUSTOMER,
                $customerId,
                time() - 1
            )
        );

        return $this->otp->generateToken($customerId);
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

    /**
     * A region_id must be one of the address country's regions (TC73 14zb93nv6vw).
     *
     * The app sends region_id (with region) when the state comes from the country's list, and only region (free
     * text) for countries without one.
     * - A region_id sent for another country is refused (400): nothing is saved.
     * - Otherwise a stored region_id that is not the resulting country's is cleared. That happens when the country
     *   changes and no region_id is sent (an Albanian region must not stay on an Egyptian address), and also
     *   repairs stale data on the next save.
     *
     * @param DataObject $vendorModel
     * @param array $changes
     * @return array
     * @throws InputException
     */
    private function normalizeRegion(DataObject $vendorModel, array $changes): array
    {
        $country = (string) ($changes['country_id'] ?? $vendorModel->getData('country_id'));

        if (array_key_exists('region_id', $changes) && (int) $changes['region_id']) {
            $region = $this->regionFactory->create()->load((int) $changes['region_id']);
            if (!$region->getId() || (string) $region->getCountryId() !== $country) {
                throw new InputException(__(
                    'Region %1 is not a region of country "%2". Choose the state again for the selected country.',
                    (int) $changes['region_id'],
                    $country
                ));
            }
            return $changes;
        }

        $storedRegionId = (int) $vendorModel->getData('region_id');
        if ($storedRegionId && !array_key_exists('region_id', $changes)) {
            $region = $this->regionFactory->create()->load($storedRegionId);
            if ((string) $region->getCountryId() !== $country) {
                $changes['region_id'] = null;
            }
        }

        return $changes;
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
