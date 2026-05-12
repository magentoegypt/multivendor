<?php
namespace MagentoEgypt\BundleExtend\Ui\DataProvider\Product;

use Magento\Catalog\Model\Product\Type;

/**
 * Replaces the core bundle "Add Products to Option" admin data provider so that the modal
 * lists products of every type that EITHER bundle or new_bundle allow as selections — i.e.
 * simple + virtual + configurable. The server-side LinkManagement / product_types.xml
 * validation rejects invalid choices for standard bundle, so admins for a standard Bundle
 * product still get blocked from saving a configurable child.
 */
class BundleDataProvider extends \Magento\Bundle\Ui\DataProvider\Product\BundleDataProvider
{
    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        if (!$this->getCollection()->isLoaded()) {
            $allowedTypes = array_unique(array_merge(
                $this->dataHelper->getAllowedSelectionTypes(),
                [Type::TYPE_SIMPLE, Type::TYPE_VIRTUAL, \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE]
            ));

            $this->getCollection()->addAttributeToFilter('type_id', $allowedTypes);
            $this->getCollection()->addStoreFilter(
                \Magento\Store\Model\Store::DEFAULT_STORE_ID
            );
            $this->getCollection()->load();
        }
        $items = $this->getCollection()->toArray();

        return [
            'totalRecords' => $this->getCollection()->getSize(),
            'items' => array_values($items),
        ];
    }
}
