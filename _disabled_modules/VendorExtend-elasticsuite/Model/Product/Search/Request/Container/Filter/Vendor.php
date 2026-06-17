<?php
namespace MagentoEgypt\VendorExtend\Model\Product\Search\Request\Container\Filter;

use Smile\ElasticsuiteCore\Api\Search\Request\Container\FilterInterface;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

class Vendor implements FilterInterface
{
    /**
     * @var \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory
     */
    private $queryFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    private $coreRegistry;

    /**
     * Search Blacklist filter constructor.
     *
     * @param \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory $queryFactory       Query Factory
     * @param \Magento\Framework\Registry                               $coreRegistry       Registry.
     */
    public function __construct(\Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory $queryFactory, \Magento\Framework\Registry $coreRegistry)
    {
        $this->queryFactory  = $queryFactory;
        $this->coreRegistry  = $coreRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilterQuery()
    {
        $query = null;
        $vendor = $this->getVendor();
        if($vendor) {
            $query = $this->queryFactory->create(
                QueryInterface::TYPE_TERMS,
                [
                    'field' => 'vendor_id',
                    'values' => [ $vendor->getId() ],
                ]
            );
        }

        return $query;
    }

    public function getVendor()
    {
        return $this->coreRegistry->registry('vendor');
    }
}
