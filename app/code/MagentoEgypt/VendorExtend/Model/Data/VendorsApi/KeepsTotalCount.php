<?php
declare(strict_types=1);

namespace MagentoEgypt\VendorExtend\Model\Data\VendorsApi;

/**
 * The real total for Vnecoms' seller API list results.
 *
 * Vnecoms' Order/Invoice/Memo/Shipment/Withdrawal SearchResult classes ignore setTotalCount() and
 * answer getTotalCount() with count($items), so GET /V1/vendors/order returned total_count 20 on
 * a 20-row page for a seller with 91 orders, and the app could not tell how many pages there were.
 * Their repositories do pass $collection->getSize(); this keeps it.
 */
trait KeepsTotalCount
{
    private ?int $hmTotalCount = null;

    /**
     * @param int $totalCount
     * @return $this
     */
    public function setTotalCount($totalCount)
    {
        $this->hmTotalCount = $totalCount === null ? null : (int) $totalCount;

        return $this;
    }

    /**
     * @return int
     */
    public function getTotalCount()
    {
        return $this->hmTotalCount ?? parent::getTotalCount();
    }
}
