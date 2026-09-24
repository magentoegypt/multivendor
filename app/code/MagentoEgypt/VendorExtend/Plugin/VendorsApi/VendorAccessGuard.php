<?php
declare(strict_types=1);

namespace MagentoEgypt\VendorExtend\Plugin\VendorsApi;

use Magento\Framework\Webapi\Exception as WebapiException;
use Vnecoms\Vendors\Model\Vendor;
use Vnecoms\Vendors\Model\VendorFactory;

/**
 * Seller-status gate for every vendor REST call made with a customer token (REST area only).
 *
 * Vnecoms rejects a customer whose seller account is missing or not approved with an
 * AuthorizationException. The Web API maps that to 401, so a correctly signed-in but
 * PENDING seller looked exactly like a bad token (vendor app ticket 86d4b0z85: "OTP login
 * succeeds, first vendor call 401"). On /V1/vendors/me it was worse: VendorRepository::getById
 * wraps every exception, so the same seller got a 400 "Could not save the page".
 *
 * Here the same checks answer 403 with a message that says which state the account is in,
 * so the app can show "pending approval" instead of "incorrect password".
 */
class VendorAccessGuard
{
    private VendorFactory $vendorFactory;

    public function __construct(VendorFactory $vendorFactory)
    {
        $this->vendorFactory = $vendorFactory;
    }

    /**
     * @see \Vnecoms\VendorsApi\Helper\Data::getVendorByCustomer()
     */
    public function aroundGetVendorByCustomer($subject, callable $proceed, $customer)
    {
        $this->assertApprovedSeller((int) $customer->getId());
        return $proceed($customer);
    }

    /**
     * GET /V1/vendors/me. Checked BEFORE Vnecoms' catch-all turns the refusal into a 400.
     *
     * @see \Vnecoms\VendorsApi\Model\VendorRepository::getById()
     */
    public function aroundGetById($subject, callable $proceed, $customerId)
    {
        $this->assertApprovedSeller((int) $customerId);
        return $proceed($customerId);
    }

    /**
     * @param int $customerId
     * @return Vendor
     * @throws WebapiException 403
     */
    public function assertApprovedSeller(int $customerId): Vendor
    {
        $vendor = $this->loadByCustomerId($customerId);
        if (!$vendor->getId()) {
            throw $this->forbidden(__('This account is not registered as a seller.'));
        }

        switch ((int) $vendor->getStatus()) {
            case Vendor::STATUS_APPROVED:
                return $vendor;
            case Vendor::STATUS_PENDING:
                throw $this->forbidden(
                    __('Your seller account is pending approval. You can sign in once it has been approved.')
                );
            case Vendor::STATUS_EXPIRED:
                throw $this->forbidden(__('Your seller account has expired. Please contact support.'));
            default:
                throw $this->forbidden(__('Your seller account is disabled. Please contact support.'));
        }
    }

    /**
     * The seller linked to a customer, whatever its status (empty model when there is none).
     */
    public function loadByCustomerId(int $customerId): Vendor
    {
        $vendor = $this->vendorFactory->create();
        if (!$customerId) {
            return $vendor;
        }
        /* Same lookup as the resource's loadByCustomer(), which insists on a Customer model. */
        $resource = $vendor->getResource();
        $connection = $resource->getConnection();
        $vendorId = (int) $connection->fetchOne(
            $connection->select()
                ->from($resource->getTable('ves_vendor_user'), 'vendor_id')
                ->where('customer_id = ?', $customerId)
                ->limit(1)
        );
        if ($vendorId) {
            $vendor->load($vendorId);
        }
        return $vendor;
    }

    private function forbidden(\Magento\Framework\Phrase $message): WebapiException
    {
        return new WebapiException($message, 0, WebapiException::HTTP_FORBIDDEN);
    }
}
