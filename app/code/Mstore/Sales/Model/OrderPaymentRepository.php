<?php

namespace Mstore\Sales\Model;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\CreditmemoSearchResultInterfaceFactory as SearchResultFactory;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Mstore\Sales\Api\OrderPaymentRepositoryInterface as MstoreOrderPaymentRepositoryInterface;

class OrderPaymentRepository implements MstoreOrderPaymentRepositoryInterface
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
     * @var OrderPaymentRepositoryInterface
     */
    private $orderPaymentRepository;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * Order payment constructor.
     * @param FilterBuilder $filterBuilder
     * @param OrderPaymentRepositoryInterface $orderPaymentRepository
     * @param SearchResultFactory $searchResultFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param FilterGroup $filterGroup
     */
    public function __construct(
        FilterBuilder $filterBuilder,
        OrderPaymentRepositoryInterface $orderPaymentRepository,
        SearchResultFactory $searchResultFactory,
        OrderRepositoryInterface $orderRepository,
        FilterGroup $filterGroup
    ) {
        $this->orderRepository = $orderRepository;
        $this->searchResultFactory = $searchResultFactory;
        $this->filterGroup = $filterGroup;
        $this->filterBuilder = $filterBuilder;
        $this->orderPaymentRepository = $orderPaymentRepository;
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

        return $this->orderPaymentRepository->getList($searchCriteria);
    }
}
