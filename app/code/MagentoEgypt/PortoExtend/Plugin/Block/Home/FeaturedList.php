<?php 
namespace MagentoEgypt\PortoExtend\Plugin\Block\Home;

use Vnecoms\VendorsCategory\Api\CategoryRepositoryInterface;

class FeaturedList
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
                ->addAttributeToFilter('sw_featured', 1, 'left')
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
                
            $collection->addAttributeToSort('created_at','desc')
                ->addAttributeToFilter('vendor_id', $vendor->getVendorId());

            $collection->addAttributeToFilter('approval', \Vnecoms\VendorsProduct\Model\Source\Approval::STATUS_APPROVED);

            $collection->getSelect()
                ->order('rand()')
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