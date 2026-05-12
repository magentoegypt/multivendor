<?php
namespace MagentoEgypt\BundleExtend\Plugin\Model;

use Magento\Framework\App\ResourceConnection;
use MagentoEgypt\BundleExtend\Helper\Data as BundleExtendHelper;

class ApplyBundleDiscount
{
    /**
     * @var ResourceConnection
     */
    protected $resource;

    public function __construct(ResourceConnection $resource)
    {
        $this->resource = $resource;
    }

    public function afterGetSelectionFinalTotalPrice(
        \Magento\Bundle\Model\Product\Price $subject,
        $result,
        $product,
        $selection,
        $bundleQty,
        $selectionQty,
        $multiplyQty = true,
        $takeTierPrice = true
    ) {
        if (!$product || $product->getTypeId() !== BundleExtendHelper::NEW_BUNDLE_TYPE_CODE) {
            return $result;
        }

        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('catalog_product_bundle_option');

        $optionId = $selection->getOptionId();

        $row = $connection->fetchRow(
            "SELECT discount_type, discount_amount
             FROM $table
             WHERE option_id = ?",
            $optionId
        );

        if (!$row || !$row['discount_amount']) {
            return $result;
        }

        $discountType = $row['discount_type'];
        $discountAmount = (float)$row['discount_amount'];

        if ($discountType === 'fixed') {
            $result -= $discountAmount;
        } elseif ($discountType === 'percent') {
            $result -= ($result * $discountAmount / 100);
        }

        return max(0, $result);
    }
}
