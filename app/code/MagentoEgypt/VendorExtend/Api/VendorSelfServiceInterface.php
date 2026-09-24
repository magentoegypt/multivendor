<?php
namespace MagentoEgypt\VendorExtend\Api;

/**
 * What the vendor app needs WITHOUT the admin integration token.
 *
 * Every method except register() is bound to the seller's own customer token: the route
 * forces customerId from %customer_id%, so a client-sent id can never select another seller.
 */
interface VendorSelfServiceInterface
{
    /**
     * PUT /V1/vendors/me — update the caller's own seller profile.
     *
     * id, vendor_id, status, group_id, customer_id and email in the payload are ignored.
     *
     * @param int $customerId
     * @param \Vnecoms\VendorsApi\Api\Data\VendorInterface $vendor
     * @return \Vnecoms\VendorsApi\Api\Data\VendorInterface
     */
    public function updateMe($customerId, \Vnecoms\VendorsApi\Api\Data\VendorInterface $vendor);

    /**
     * DELETE /V1/vendors/me — delete the caller's own seller account (any approval status).
     *
     * @param int $customerId
     * @return bool
     */
    public function deleteMe($customerId);

    /**
     * GET /V1/vendors/me/stockItems/:sku — stock of one of the caller's own products.
     *
     * @param int $customerId
     * @param string $sku
     * @return \Magento\CatalogInventory\Api\Data\StockItemInterface
     */
    public function getStockItem($customerId, $sku);

    /**
     * GET /V1/vendors/me/categories — the category tree, for the product form.
     *
     * @param int $customerId
     * @param int|null $rootCategoryId
     * @param int|null $depth
     * @return \Magento\Catalog\Api\Data\CategoryTreeInterface
     */
    public function getCategories($customerId, $rootCategoryId = null, $depth = null);

    /**
     * POST /V1/vendors/register — anonymous seller registration, gated by a verified WhatsApp number.
     *
     * registrationToken is the `token` that POST /V1/whatsapp/otp/verify returns for
     * type VENDOR_REGISTER; it is single-use, expires after 30 minutes, and fixes the
     * seller's telephone to the number that was verified.
     *
     * @param \Vnecoms\VendorsApi\Api\Data\VendorInterface $vendor
     * @param string $password
     * @param string $registrationToken
     * @return \Vnecoms\VendorsApi\Api\Data\VendorInterface
     */
    public function register(\Vnecoms\VendorsApi\Api\Data\VendorInterface $vendor, $password, $registrationToken);
}
