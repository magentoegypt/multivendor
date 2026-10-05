<?php
declare(strict_types=1);

namespace MagentoEgypt\VendorExtend\Model\Data\VendorsApi;

/**
 * \Vnecoms\VendorsApi\Model\Data\Sale\OrderSearchResult with a real total_count (see KeepsTotalCount)
 * and each order's share of the shipping (see SellerShippingShare), so GET /V1/vendors/order lists
 * the same totals as GET /V1/vendor/order/:orderId.
 */
class OrderSearchResult extends \Vnecoms\VendorsApi\Model\Data\Sale\OrderSearchResult
{
    use KeepsTotalCount;
    use \MagentoEgypt\VendorExtend\Model\SellerShippingShare;

    /**
     * @param \Vnecoms\VendorsApi\Api\Data\Sale\OrderInterface[]|null $items
     * @return $this
     */
    public function setItems(?array $items = null)
    {
        foreach ((array) $items as $item) {
            if ($item instanceof \Vnecoms\VendorsApi\Api\Data\Sale\OrderInterface) {
                $this->hmApplySellerShippingShare($item);
            }
        }

        return parent::setItems($items);
    }
}
