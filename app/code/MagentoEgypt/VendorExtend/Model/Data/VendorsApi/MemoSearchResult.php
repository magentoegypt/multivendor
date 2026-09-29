<?php
declare(strict_types=1);

namespace MagentoEgypt\VendorExtend\Model\Data\VendorsApi;

/** \Vnecoms\VendorsApi\Model\Data\Sale\MemoSearchResult with a real total_count; see KeepsTotalCount. */
class MemoSearchResult extends \Vnecoms\VendorsApi\Model\Data\Sale\MemoSearchResult
{
    use KeepsTotalCount;
}
