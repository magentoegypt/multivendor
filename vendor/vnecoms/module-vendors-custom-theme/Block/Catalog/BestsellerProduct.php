<?php

namespace Vnecoms\VendorsCustomTheme\Block\Catalog;

use Magento\Widget\Block\BlockInterface;

/**
 * New products app.
 */
class BestsellerProduct extends \Vnecoms\VendorsCustomTheme\Block\Catalog\ProductsList implements BlockInterface
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
     * BestsellerProduct constructor.
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
            'VNECOMS_VENDORS_BESTSELLER_PRODUCTS',
            $this->_storeManager->getStore()->getId(),
            $this->_design->getDesignTheme()->getId(),
            $this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_GROUP),
            intval($this->getRequest()->getParam($this->getData('page_var_name'), 1)),
            $this->getProductsPerPage(),
            $conditions . $this->getData('id'),
            version_compare($version, '2.2.0', '<') ? serialize($this->getRequest()->getParams()) : json_encode($this->getRequest()->getParams())
        ];
	}

    /**
     * Create Collection
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function createCollection()
    {
        $collection = parent::createCollection();

		$period = $this->getSortPeriod()?$this->getSortPeriod():'monthly';
		$dateObj = \Magento\Framework\App\ObjectManager::getInstance()->create('Magento\Framework\Stdlib\DateTime\DateTime');
		$date = $dateObj->date('Y-m-d');
		if ($period == 'monthly') {
			$date = $dateObj->date('Y-m-01');
		} elseif ($period == 'yearly') {
			$date = $dateObj->date('Y-01-01');
		}
		
		$table = 'sales_bestsellers_aggregated_'.$period;
		$collection->joinTable(
			['bestsellers' => $collection->getTable($table)],
			'product_id=entity_id',
			['qty_ordered' => 'qty_ordered'],
			[
				'store_id' => $this->_storeManager->getStore()->getId(),
				'period' => $date
			],
			'left'
		);
		$collection->getSelect()->order('bestsellers.qty_ordered desc');
		$collection->setOrder('created_at', 'DESC');
        return $collection;
    }
}
