<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsPriceComparison\Ui\Component\Listing\Attribute;

class Repository extends \Magento\Catalog\Ui\Component\Listing\Attribute\AbstractRepository
{
    /**
     * @var \Vnecoms\VendorsPriceComparison\Helper\Data
     */
    protected $_helper;
    
    public function __construct(
        \Magento\Catalog\Api\ProductAttributeRepositoryInterface $productAttributeRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Vnecoms\VendorsPriceComparison\Helper\Data $helper
    ) {
        parent::__construct($productAttributeRepository, $searchCriteriaBuilder);
        $this->_helper = $helper;
    }
    
    /**
     * {@inheritdoc}
     */
    protected function buildSearchCriteria()
    {
        return $this->searchCriteriaBuilder->addFilter('main_table.attribute_code', $this->_helper->getFilterAttributes(),'in')->create();
    }
}
