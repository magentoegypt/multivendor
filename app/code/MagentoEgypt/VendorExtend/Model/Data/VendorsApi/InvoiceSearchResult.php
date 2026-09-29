<?php
declare(strict_types=1);

namespace MagentoEgypt\VendorExtend\Model\Data\VendorsApi;

/** \Vnecoms\VendorsApi\Model\Data\Sale\InvoiceSearchResult with a real total_count; see KeepsTotalCount. */
class InvoiceSearchResult extends \Vnecoms\VendorsApi\Model\Data\Sale\InvoiceSearchResult
{
    use KeepsTotalCount;
}
