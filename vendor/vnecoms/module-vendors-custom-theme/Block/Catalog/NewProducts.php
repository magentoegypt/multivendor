<?php

namespace Vnecoms\VendorsCustomTheme\Block\Catalog;

use Magento\Widget\Block\BlockInterface;

/**
 * New products app.
 */
class NewProducts extends \Vnecoms\VendorsCustomTheme\Block\Catalog\ProductsList implements BlockInterface
{
    /**
     * @var \Magento\Framework\App\ProductMetadata
     */
    protected $productMetadata;

    protected function _construct()
    {
        parent::_construct();
        $this->addData(
            ['cache_lifetime' => false]
        );
    }

    /**
     * NewProducts constructor.
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Magento\Catalog\Model\Product\Visibility $catalogProductVisibility
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param \Magento\Rule\Model\Condition\Sql\Builder $sqlBuilder
     * @param \Magento\CatalogWidget\Model\Rule $rule
     * @param \Magento\Widget\Helper\Conditions $conditionsHelper
     * @param \Vnecoms\Vendors\Model\VendorFactory $vendorFactory
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Magento\Framework\App\ProductMetadata $productMetadata
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Model\Product\Visibility $catalogProductVisibility,
        \Magento\Framework\App\Http\Context $httpContext,
        \Magento\Rule\Model\Condition\Sql\Builder $sqlBuilder,
        \Magento\CatalogWidget\Model\Rule $rule,
        \Magento\Widget\Helper\Conditions $conditionsHelper,
        \Vnecoms\Vendors\Model\VendorFactory $vendorFactory,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\App\ProductMetadata $productMetadata,
        array $data = []
    ) {
        parent::__construct($context, $productCollectionFactory, $catalogProductVisibility, $httpContext, $sqlBuilder, $rule, $conditionsHelper, $vendorFactory, $moduleManager, $data);
        $this->productMetadata = $productMetadata;
    }

    /**
     * Get cache key info
     *
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
	public function getCacheKeyInfo()
    {
        $version = $this->productMetadata->getVersion();
        $conditions = $this->getData('conditions')
            ? $this->getData('conditions')
            : $this->getData('conditions_encoded');

		return [
            'VNECOMS_VENDORS_NEW_PRODUCTS',
            $this->_storeManager->getStore()->getId(),
            $this->_design->getDesignTheme()->getId(),
            $this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_GROUP),
            intval($this->getRequest()->getParam($this->getData('page_var_name'), 1)),
            $this->getProductsPerPage(),
            $conditions . $this->getData('id'),
            version_compare($version, '2.2.0', '<') ? serialize($this->getRequest()->getParams()) : json_encode($this->getRequest()->getParams())
        ];
	}
	
    public function createCollection()
    {
		$todayStartOfDayDate = $this->_localeDate->date()->setTime(0, 0, 0)->format('Y-m-d H:i:s');
		$todayEndOfDayDate = $this->_localeDate->date()->setTime(23, 59, 59)->format('Y-m-d H:i:s');
        $collection = parent::createCollection()->addAttributeToFilter(
            'news_from_date',
            [
                'or' => [
                    0 => ['date' => true, 'to' => $todayEndOfDayDate],
                    1 => ['is' => new \Zend_Db_Expr('null')],
                ]
            ],
            'left'
        )->addAttributeToFilter(
            'news_to_date',
            [
                'or' => [
                    0 => ['date' => true, 'from' => $todayStartOfDayDate],
                    1 => ['is' => new \Zend_Db_Expr('null')],
                ]
            ],
            'left'
        )->addAttributeToFilter(
            [
                ['attribute' => 'news_from_date', 'is' => new \Zend_Db_Expr('not null')],
                ['attribute' => 'news_to_date', 'is' => new \Zend_Db_Expr('not null')],
            ]
        );
		

		$collection->setOrder('created_at', 'DESC');
        return $collection;
    }
}
