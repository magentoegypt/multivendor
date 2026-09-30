<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppVendors\Model\Store;

use Magento\Framework\Module\Manager as ModuleManager;
use MagentoEgypt\HubApp\Model\Seller\SellerDirectory;

/**
 * A seller's value in the storefront's Algolia seller facet (HmStoreCard.facet_value).
 *
 * The facet is the `seller` attribute AlgoliaVendor AddSellerData puts on every
 * product record of an approved seller: SellerResolver::getRawName() — company
 * when it is a name, else the humanised seller code — through __() inside the
 * indexer's emulation of the store view. That is exactly the rule of the
 * seller names here (SellerName::source() through __() in one storefront
 * emulation per store view, SellerDirectory), which a unit test pins against
 * SellerResolver itself; so the value is read from the same cached name map.
 *
 * Null when AlgoliaVendor is off (records then carry no seller) or the seller
 * has no name to index (getRawName() gives null for an empty one).
 */
class SellerFacetValues
{
    public const ALGOLIA_VENDOR_MODULE = 'MagentoEgypt_AlgoliaVendor';

    public function __construct(
        private readonly ModuleManager $moduleManager,
        private readonly SellerDirectory $directory
    ) {
    }

    public function forVendor(int $vendorId, int $storeId): ?string
    {
        if ($vendorId < 1 || !$this->moduleManager->isEnabled(self::ALGOLIA_VENDOR_MODULE)) {
            return null;
        }
        //  Products of sellers that are not approved are kept out of the index.
        if ($this->directory->getApproved($vendorId) === null) {
            return null;
        }
        $value = (string) ($this->directory->names([$vendorId], $storeId)[$vendorId] ?? '');

        return $value !== '' ? $value : null;
    }
}
