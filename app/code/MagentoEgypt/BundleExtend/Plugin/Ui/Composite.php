<?php
namespace MagentoEgypt\BundleExtend\Plugin\Ui;

use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Framework\App\ResourceConnection;
use MagentoEgypt\BundleExtend\Helper\Data as BundleExtendHelper;

class Composite
{
    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * @var LocatorInterface
     */
    private $locator;

    public function __construct(
        ResourceConnection $resource,
        LocatorInterface $locator
    ) {
        $this->resource = $resource;
        $this->locator = $locator;
    }

    public function afterModifyData(
        \Magento\Bundle\Ui\DataProvider\Product\Form\Modifier\Composite $subject,
        $data
    ) {
        if ($this->locator->getProduct()->getTypeId() !== BundleExtendHelper::NEW_BUNDLE_TYPE_CODE) {
            return $data;
        }

        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('catalog_product_bundle_option');

        foreach ($data as $productId => &$productData) {
            if (!isset($productData['bundle_options'])) {
                continue;
            }

            foreach ($productData['bundle_options']['bundle_options'] as $idx => &$option) {

                if (!isset($option['option_id'])) {
                    continue;
                }

                $row = $connection->fetchRow(
                    "SELECT discount_type, discount_amount
                     FROM $table
                     WHERE option_id = ?",
                    $option['option_id']
                );

                if ($row) {
                    $option['discount_type'] = $row['discount_type'];
                    $option['discount_amount'] = round((float)$row['discount_amount'], 2);
                }
            }
        }

        return $data;
    }
}
