<?php 
namespace MagentoEgypt\PortoExtend\Plugin\Block\Home;

use Vnecoms\VendorsCategory\Api\CategoryRepositoryInterface;

class BestsellersList
{
    protected $categoryRepository;
    protected $_collection;
    protected $_resource;
    protected $_coreRegistry;
    protected $_catalogConfig;

    public function __construct(
        \Magento\Catalog\Model\Config $_catalogConfig,
        CategoryRepositoryInterface $categoryRepository,
        \Magento\Catalog\Model\ResourceModel\Product\Collection $collection,
        \Magento\Framework\Registry $_coreRegistry,
        \Magento\Framework\App\ResourceConnection $resource
    ) {
        $this->_catalogConfig = $_catalogConfig;
        $this->categoryRepository = $categoryRepository;
        $this->_collection = $collection;
        $this->_resource = $resource;
        $this->_coreRegistry = $_coreRegistry;    
    }
    
    public function aroundGetProducts($subject, callable $proceed)
	{
        $vendor = $this->getVendor();
        if(!empty($vendor)) {
            $count = $subject->getProductCount();
            $collection = clone $this->_collection;
            $collection->clear()->getSelect()->reset(\Magento\Framework\DB\Select::WHERE)->reset(\Magento\Framework\DB\Select::ORDER)->reset(\Magento\Framework\DB\Select::LIMIT_COUNT)->reset(\Magento\Framework\DB\Select::LIMIT_OFFSET)->reset(\Magento\Framework\DB\Select::GROUP);
            
            $collection->addMinimalPrice()
                ->addFinalPrice()
                ->addTaxPercents()
                ->addAttributeToSelect('name')
                ->addAttributeToSelect('image')
                ->addAttributeToSelect('small_image')
                ->addAttributeToSelect('thumbnail')
                ->addAttributeToSelect($this->_catalogConfig->getProductAttributes())
                ->addAttributeToFilter('status',1)
                ->addUrlRewrite();
            
            $category_id = $subject->getData("category_id");
            if($category_id>0) {
                $vendorCategory = $this->categoryRepository->get($category_id);
                $collection->joinField(
                    'position',
                    $this->_resource->getTableName('ves_vendorscategory_category_product'),
                    'position',
                    'product_id=entity_id',
                    'at_position.category_id='.(int) $vendorCategory->getId(),
                    'left'
                )->getSelect()->where('at_position.category_id = ?', $vendorCategory->getId());
            }
                
            $collection->addAttributeToFilter('vendor_id', $vendor->getVendorId());
            $collection->addAttributeToFilter('approval', \Vnecoms\VendorsProduct\Model\Source\Approval::STATUS_APPROVED);

            $collection->getSelect()
                ->joinLeft(['soi' => $collection->getTable('sales_order_item')], 'soi.product_id = e.entity_id', ['SUM(soi.qty_ordered) AS ordered_qty'])
                ->join(['order' => $collection->getTable('sales_order')], "order.entity_id = soi.order_id",['order.state'])
                ->where("order.state <> 'canceled' and soi.parent_item_id IS NULL AND soi.product_id IS NOT NULL")
                ->group('soi.product_id')
                ->order('ordered_qty DESC')
                ->limit($count);

            return $collection;
        }
		return $proceed();
	}

	public function getVendor()
    {
        return $this->_coreRegistry->registry('current_vendor_domain');
    }

}