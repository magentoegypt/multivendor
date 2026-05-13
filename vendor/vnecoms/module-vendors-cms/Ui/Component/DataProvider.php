<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Ui\Component;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\Reporting;
use Vnecoms\VendorsCms\Model\ResourceModel\Page\CollectionFactory;

class DataProvider extends \Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider
{
    /**
     * CMS Page collection.
     *
     * @var \Vnecoms\VendorsCms\Model\ResourceModel\Page\Collection
     */
    protected $collection;

    /**
     * @var \Magento\Ui\DataProvider\AddFieldToCollectionInterface[]
     */
    protected $addFieldStrategies;

    /**
     * @var \Magento\Ui\DataProvider\AddFilterToCollectionInterface[]
     */
    protected $addFilterStrategies;

    protected $vendorSession;

    /**
     * DataProvider constructor.
     *
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param Reporting             $reporting
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RequestInterface      $request
     * @param FilterBuilder         $filterBuilder
     * @param CollectionFactory     $collectionFactory
     * @param array                 $meta
     * @param array                 $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        Reporting $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        CollectionFactory $collectionFactory,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $reporting,
            $searchCriteriaBuilder,
            $request,
            $filterBuilder,
            $meta,
            $data
        );
      //  $this->collection = $collectionFactory->create();
   //     $this->collection->addVendorFilter('vendor_id',$this->getVendor()->getId());

       // echo $this->collection->getSelect();
    }

    public function getVendor()
    {
        if ($this->vendorSession) {
            return $this->vendorSession->getVendor();
        }
        $this->vendorSession = \Magento\Framework\App\ObjectManager::getInstance()->get('Vnecoms\Vendors\Model\Session');

        return $this->vendorSession->getVendor();
    }
}
