<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppBundle\Model\Cart;

use Magento\Framework\DataObject;

/**
 * Keeps a bundle buy request's per-child configurable choices intact while the
 * bundle's selections are prepared one after another.
 *
 * The website posts super_attribute[<child product id>][<attribute id>] and
 * BundleExtend's SetSuperConfigurableProduct plugin narrows it, in place on the
 * ONE buy request the bundle type hands to every selection, to the child being
 * prepared. The next configurable child then no longer finds its own entry and
 * is prepared with the previous child's choices. hmAddBundleToCart remembers the
 * full map here and a graphql-area plugin (KeepPerChildSuperAttributes) puts it
 * back after each configurable child, so every child gets its own choice. Only
 * buy requests remembered here are touched: core addProductsToCart and the
 * website are unaffected.
 *
 * Per request, keyed by object id; hmAddBundleToCart forgets the request when done.
 */
class SuperAttributeKeeper
{
    /** @var array<int, array<int|string, mixed>> spl_object_id => super_attribute map */
    private array $maps = [];

    public function remember(DataObject $buyRequest): void
    {
        $map = $buyRequest->getData('super_attribute');
        if (is_array($map) && $map) {
            $this->maps[spl_object_id($buyRequest)] = $map;
        }
    }

    public function restore(DataObject $buyRequest): void
    {
        $id = spl_object_id($buyRequest);
        if (isset($this->maps[$id])) {
            $buyRequest->setData('super_attribute', $this->maps[$id]);
        }
    }

    public function forget(DataObject $buyRequest): void
    {
        unset($this->maps[spl_object_id($buyRequest)]);
    }
}
