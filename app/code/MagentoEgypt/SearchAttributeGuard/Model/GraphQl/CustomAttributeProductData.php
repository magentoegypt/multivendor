<?php
declare(strict_types=1);

namespace MagentoEgypt\SearchAttributeGuard\Model\GraphQl;

use Magento\CatalogGraphQl\Model\ProductDataProvider;

/**
 * Product data for `custom_attributesV2`, with this catalogue's `model` attribute intact.
 *
 * The catalogue has a user-defined product attribute whose code is `model` (id 315, text).
 * Core's ProductDataProvider::getProductDataById() returns $product->toArray() and then sets
 * $productData['model'] = $product, which is how the order, wishlist and variant resolvers pass
 * the product along. ProductCustomAttributes reads attribute values from that same array, so
 * for `model` it handed the Product OBJECT to GetAttributeValueComposite::execute(string $value).
 * That TypeError failed custom_attributesV2 for every product ("Internal server error",
 * 2026-09-26).
 *
 * Only ProductCustomAttributes is given this class (etc/graphql/di.xml). It keeps the
 * attribute's real value under `model`, and every other caller keeps core's product object.
 */
class CustomAttributeProductData extends ProductDataProvider
{
    public function getProductDataById(int $productId): array
    {
        $productData = parent::getProductDataById($productId);
        $product = $productData['model'];
        $value = $product->getData('model');
        if ($value === null || is_scalar($value)) {
            $productData['model'] = $value;
        } else {
            unset($productData['model']);
        }

        return $productData;
    }
}
