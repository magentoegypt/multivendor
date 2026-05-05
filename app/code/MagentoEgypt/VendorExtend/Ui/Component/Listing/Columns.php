<?php 
namespace MagentoEgypt\VendorExtend\Ui\Component\Listing;

class Columns extends \Magento\Catalog\Ui\Component\Listing\Columns
{
    const UPDATED_ATTR = 'content_updated';
    protected $updated;
    protected $storeManager;
    protected $attributecollectionFactory;
    protected $attrList;

    public function __construct(
        \Magento\Framework\View\Element\UiComponent\ContextInterface $context,
        \Magento\Catalog\Ui\Component\ColumnFactory $columnFactory,
        \Magento\Catalog\Ui\Component\Listing\Attribute\RepositoryInterface $attributeRepository,
        \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $attributecollectionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $columnFactory, $attributeRepository, $components, $data);
        $this->storeManager = $storeManager;
        $this->attributecollectionFactory = $attributecollectionFactory;
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $ids = array_column($dataSource['data']['items'], 'entity_id');
            $updated = $this->getUpdated($ids);
            $currency = $this->storeManager->getStore(
                $this->context->getFilterParam('store_id', \Magento\Store\Model\Store::DEFAULT_STORE_ID)
            )->getBaseCurrencyCode();
            foreach ($dataSource['data']['items'] as & $item) {
                if($item['approval']!= 4) continue;
                $updatedAttr = [];
                if(!isset($updated[$item['entity_id']])) continue;
                $newData = array_filter($updated[$item['entity_id']]);
                foreach($newData as $attribute => $value) {
                    if($attribute == 'price') {
                        if(
                            !empty($item[$attribute]) && 
                            number_format($newData[$attribute], 2) != substr($item[$attribute],5)
                        ) {
                            $item[$attribute] = sprintf(
                                "%s \n[%s]",
                                $item[$attribute],
                                html_entity_decode( "<b>".$currency."&nbsp;".number_format($newData[$attribute], 2)."</b>" ),
                            );
                            $updatedAttr[] = $attribute;
                        }
                    } else {
                        if(
                            !empty($item[$attribute]) && 
                            $newData[$attribute] != $item[$attribute]
                        ) {
                            $item[$attribute] = $value;
                            $updatedAttr[] = $attribute;
                        } else {
                            if(in_array($attribute, ['category_ids'])) $updatedAttr[] = $attribute;
                        }
                    }
                }
                $item[self::UPDATED_ATTR] = count($updatedAttr) > 0 ? $this->getAttrList($updatedAttr) : '';
            }
        }
        return $dataSource;
    }

    protected function getAttrList($updatedAttr) {
        $labels = [];
        if($this->attrList == null) {
            $attrList = $this->attributecollectionFactory->create();
            foreach($attrList->getItems() as $attribute) {
                $this->attrList[$attribute->getAttributeCode()] = $attribute->getStoreLabel();
            }
        }
        foreach($updatedAttr as $attr) {
            if(isset($this->attrList[$attr])) {
                $labels[] = '<b>'.$this->attrList[$attr].'</b>';
            }
        }
        $labels = array_filter($labels);
        return implode(', ', $labels);
    }

    protected function getUpdated($ids) {
        if($this->updated == null) {
            $collection = \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Vnecoms\VendorsProduct\Model\Product\Update::class)->getCollection()
                ->addFieldToFilter('product_id', ['in' => $ids])
                ->addFieldToFilter('status', \Vnecoms\VendorsProduct\Model\Product\Update::STATUS_PENDING);
            foreach($collection as $update) {
                $data = unserialize($update->getProductData());
                $this->updated[$update->getProductId()] = $data;
            }
        }
        return $this->updated ?? [];
    }
}