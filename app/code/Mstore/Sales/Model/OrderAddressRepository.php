<?php declare(strict_types=1);

namespace Mstore\Sales\Model;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderAddressSearchResultInterfaceFactory as SearchResultFactory;
use Magento\Sales\Api\OrderAddressRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Mstore\Sales\Api\OrderAddressRepositoryInterface as MstoreOrderAddressRepositoryInterface;

class OrderAddressRepository implements MstoreOrderAddressRepositoryInterface
{
    /**
     * @var SearchResultFactory
     */
    private $searchResultFactory;

    /**
     * @var FilterGroup
     */
    private $filterGroup;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var OrderAddressRepositoryInterface
     */
    private $orderAddressRepository;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * Order address constructor.
     * @param FilterBuilder $filterBuilder
     * @param OrderAddressRepositoryInterface $orderAddressRepository
     * @param SearchResultFactory $searchResultFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param FilterGroup $filterGroup
     */
    public function __construct(
        FilterBuilder $filterBuilder,
        OrderAddressRepositoryInterface $orderAddressRepository,
        SearchResultFactory $searchResultFactory,
        OrderRepositoryInterface $orderRepository,
        FilterGroup $filterGroup
    ) {
        $this->orderRepository = $orderRepository;
        $this->searchResultFactory = $searchResultFactory;
        $this->filterGroup = $filterGroup;
        $this->filterBuilder = $filterBuilder;
        $this->orderAddressRepository = $orderAddressRepository;
    }

    /**
     * @inheritDoc
     */
    public function getList($customerId, $orderId, \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria)
    {
        $order = $this->orderRepository->get($orderId);
        if ($customerId =! $order->getCustomerId()) {
            throw new LocalizedException(__('Cannot view the order'));
        }
        $filterGroups = $searchCriteria->getFilterGroups();
        $this->filterGroup->setFilters([
            $this->filterBuilder
                ->setField('parent_id')
                ->setConditionType('eq')
                ->setValue($orderId)
                ->create()
        ]);
        $filterGroups = array_merge($filterGroups, [$this->filterGroup]);
        $searchCriteria->setFilterGroups($filterGroups);

        return $this->orderAddressRepository->getList($searchCriteria);
    }
}
