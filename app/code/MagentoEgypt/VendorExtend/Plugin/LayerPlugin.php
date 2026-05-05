<?php
namespace MagentoEgypt\VendorExtend\Plugin;

use Vnecoms\VendorsProduct\Model\Source\Approval as ProductApproval;

class LayerPlugin
{
    /**
     * @var \Magento\Search\Model\QueryFactory
     */
    protected $queryFactory;

    /**
     * Catalog config
     *
     * @var \Magento\Catalog\Model\Config
     */
    private $catalogConfig;

    /**
     * @var \Smile\ElasticsuiteCore\Helper\Mapping
     */
    private $mappingHelper;

    /**
     * @var \Magento\Framework\Registry
     */
    private $_coreRegistry;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    /**
     * Constructor.
     *
     * @param \Magento\Search\Model\QueryFactory     $queryFactory  Search query factory.
     * @param \Magento\Catalog\Model\Config          $catalogConfig Catalog Configuration.
     * @param \Smile\ElasticsuiteCore\Helper\Mapping $mappingHelper Mapping Helper.
     * @param \Magento\Framework\App\RequestInterface $request Http Request Ibterface.
     * @param \Magento\Framework\Registry            $_coreRegistry Registry.
     */
    public function __construct(
        \Magento\Search\Model\QueryFactory $queryFactory,
        \Magento\Catalog\Model\Config $catalogConfig,
        \Smile\ElasticsuiteCore\Helper\Mapping $mappingHelper,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Registry $registry
    ) {
        $this->queryFactory  = $queryFactory;
        $this->catalogConfig = $catalogConfig;
        $this->mappingHelper = $mappingHelper;
        $this->_coreRegistry = $registry;
        $this->request = $request;
    }


    /**
     * {@inheritDoc}
     */
    public function beforePrepareProductCollection(
        \Magento\Catalog\Model\Layer $layer,
        \Magento\Catalog\Model\ResourceModel\Collection\AbstractCollection $collection
    ) {
        $this->setSortParams($layer, $collection);
    }

    /**
     * Apply sort params to the collection.
     *
     * @param \Magento\Catalog\Model\Layer                                       $layer      Catalog / search layer.
     * @param \Magento\Catalog\Model\ResourceModel\Collection\AbstractCollection $collection Product collection.
     *
     * @return $this
     */
    private function setSortParams(
        \Magento\Catalog\Model\Layer $layer,
        \Magento\Catalog\Model\ResourceModel\Collection\AbstractCollection $collection
    ) {
        $vendor = $this->getVendor();
        $searchQuery = $this->queryFactory->get();

        if (!$searchQuery->getQueryText() && $layer->getCurrentCategory() && empty($vendor)) {
            $categoryId = $layer->getCurrentCategory()->getId();
            $sortFilter = ['category.category_id' => $categoryId];
            if($this->request->getModuleName()!='brand') 
                $collection->addSortFilterParameters('position', 'category.position', 'category', $sortFilter);
        } elseif ($searchQuery->getId()) {
            $sortFilter = ['search_query.query_id' => $searchQuery->getId()];
            if($this->request->getModuleName()!='vendorspage') 
                $collection->addSortFilterParameters('relevance', 'search_query.position', 'search_query', $sortFilter);
        }

        foreach ($this->catalogConfig->getAttributesUsedForSortBy() as $attributeCode => $attribute) {
            if ($attribute->usesSource()) {
                $sortField = $this->mappingHelper->getOptionTextFieldName($attributeCode);
                $collection->addSortFilterParameters($attributeCode, $sortField);
            }
        }

        return $this;
    }

    /**
     * Get current vendor
     *
     * @return \Vnecoms\Vendors\Model\Vendor
     */
    public function getVendor()
    {
        return $this->_coreRegistry->registry('vendor');
    }
}
