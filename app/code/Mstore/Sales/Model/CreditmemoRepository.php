<?php declare(strict_types=1);

namespace Mstore\Sales\Model;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\CreditmemoSearchResultInterfaceFactory as SearchResultFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Mstore\Sales\Api\CreditmemoRepositoryInterface as MstoreCreditmemoRepositoryInterface;

class CreditmemoRepository implements MstoreCreditmemoRepositoryInterface
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
     * @var CreditmemoRepositoryInterface
     */
    private $creditmemoRepository;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * InvoiceRepository constructor.
     * @param FilterBuilder $filterBuilder
     * @param CreditmemoRepositoryInterface $creditmemoRepository
     * @param SearchResultFactory $searchResultFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param FilterGroup $filterGroup
     */
    public function __construct(
        FilterBuilder $filterBuilder,
        CreditmemoRepositoryInterface $creditmemoRepository,
        SearchResultFactory $searchResultFactory,
        OrderRepositoryInterface $orderRepository,
        FilterGroup $filterGroup
    ) {
        $this->orderRepository = $orderRepository;
        $this->searchResultFactory = $searchResultFactory;
        $this->filterGroup = $filterGroup;
        $this->filterBuilder = $filterBuilder;
        $this->creditmemoRepository = $creditmemoRepository;
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
                ->setField('order_id')
                ->setConditionType('eq')
                ->setValue($orderId)
                ->create()
        ]);
        $filterGroups = array_merge($filterGroups, [$this->filterGroup]);
        $searchCriteria->setFilterGroups($filterGroups);

        return $this->creditmemoRepository->getList($searchCriteria);
    }
}
