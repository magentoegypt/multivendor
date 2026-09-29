<?php
declare(strict_types=1);

namespace MagentoEgypt\VendorExtend\Model\Data\VendorsApi;

/** \Vnecoms\VendorsApi\Model\Data\Sale\OrderSearchResult with a real total_count; see KeepsTotalCount. */
class OrderSearchResult extends \Vnecoms\VendorsApi\Model\Data\Sale\OrderSearchResult
{
    use KeepsTotalCount;
}
