<?php
namespace Vnecoms\VendorsPage\Block\Home;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Vnecoms\VendorsProduct\Model\Source\Approval as ProductApproval;

class ListProduct extends \Magento\Catalog\Block\Product\ListProduct
{
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $_productCollectionFactory;

    /**
     * Catalog product visibility
     *
     * @var \Magento\Catalog\Model\Product\Visibility
     */
    protected $productVisibility;

    /**
     * Catalog config
     *
     * @var \Magento\Catalog\Model\Config
     */
    protected $catalogConfig;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Data\Helper\PostHelper $postDataHelper,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        CategoryRepositoryInterface $categoryRepository,
        \Magento\Framework\Url\Helper\Data $urlHelper,
        CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Model\Product\Visibility $productVisibility,
        array $data = []
    ) {
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->productVisibility = $productVisibility;
        $this->catalogConfig = $context->getCatalogConfig();
        $this->_coreRegistry = $context->getRegistry();
        parent::__construct($context, $postDataHelper, $layerResolver, $categoryRepository, $urlHelper, $data);
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

    /**
     * Retrieve loaded category collection
     *
     * @return AbstractCollection
     */
    protected function _getProductCollection()
    {
        if ($this->_productCollection === null) {
            $layer = $this->getLayer();
            $this->_productCollection = $layer->getProductCollection();

            $this->_productCollection->getSelect()->distinct();
        }
        return $this->_productCollection;
    }

    /**
     * Get total number of vendor's product.
     *
     * @return int
     */
    public function getTotalNumberOfProducts()
    {
        if (!$this->getData('total_num_products') && $this->getVendor()) {
            $collection = $this->getProductCollection();
            $this->setData('total_num_products', sizeof($collection));
        }

        return $this->getData('total_num_products');
    }

    /**
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    public function getProductCollection() {
        $collection = $this->_productCollectionFactory->create();
        $collection->addAttributeToFilter('vendor_id', $this->getVendor()->getId())
            ->addAttributeToFilter('approval', ProductApproval::STATUS_APPROVED)
            ->setVisibility($this->productVisibility->getVisibleInCatalogIds());
        return $collection;
    }

    /**
     * Add items link to the menu
     *
     * @see \Magento\Framework\View\Element\AbstractBlock::_prepareLayout()
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $menuBlock = $this->getLayout()->getBlock('vendor.menu.top');
        if ($menuBlock) {
            $totalProduct = $this->getTotalNumberOfProducts();
            $menuBlock->addLink(
                __("Items (%1)", $totalProduct),
                __("Items (%1)", $totalProduct),
                '#vendor-products',
                10
            );
        }

        return $this;
    }

    /**
     * Get the renderer that will be used to render the details block
     *
     * @param string|null $type
     * @return bool|\Magento\Framework\View\Element\AbstractBlock
     */
    public function getDetailsRenderer($type = null)
    {
        if ($type === null) {
            $type = 'v.default';
        }
        $rendererList = $this->getDetailsRendererList();
        if ($rendererList) {
            return $rendererList->getRenderer($type, 'v.default');
        }
        return null;
    }

    /**
     * @return string
     */
    public function getDetailsRendererListName(){
        return "category.product.type.details.renderers";
    }
}
