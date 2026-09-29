<?php
declare(strict_types=1);

namespace MagentoEgypt\VendorExtend\Model\Data\VendorsApi;

/** \Vnecoms\VendorsApi\Model\Data\Sale\ShipmentSearchResult with a real total_count; see KeepsTotalCount. */
class ShipmentSearchResult extends \Vnecoms\VendorsApi\Model\Data\Sale\ShipmentSearchResult
{
    use KeepsTotalCount;
}
