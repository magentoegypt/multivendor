<?php declare(strict_types=1);

namespace Mstore\Sales\Model;

use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Api\Data\OrderSearchResultInterfaceFactory as SearchResultFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactoryInterface;
use Mstore\Sales\Api\OrderRepositoryInterface as MstoreOrderRepositoryInterface;

class OrderRepository implements MstoreOrderRepositoryInterface
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var OrderAuthorization
     */
    private $orderAuthorization;

    /**
     * @var SearchResultFactory
     */
    private $searchResultFactory;

    /**
     * @var CollectionFactoryInterface
     */
    private $collectionFactory;

    /**
     * @var JoinProcessorInterface
     */
    private $extensionAttributesJoinProcessor;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * @var FilterBuilder
     */
    protected $filterBuilder;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var FilterGroup
     */
    private $filterGroup;

    /**
     * Order constructor.
     * @param FilterBuilder $filterBuilder
     * @param SearchResultFactory $searchResultFactory
     * @param FilterGroup $filterGroup
     * @param OrderRepositoryInterface $orderRepository
     * @param CollectionProcessorInterface $collectionProcessor
     * @param CollectionFactoryInterface $collectionFactory
     * @param JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param OrderAuthorization $orderAuthorization
     */
    public function __construct(
        FilterBuilder $filterBuilder,
        SearchResultFactory $searchResultFactory,
        FilterGroup $filterGroup,
        OrderRepositoryInterface $orderRepository,
        CollectionProcessorInterface $collectionProcessor,
        CollectionFactoryInterface $collectionFactory,
        JoinProcessorInterface $extensionAttributesJoinProcessor,
        OrderAuthorization $orderAuthorization
    ) {
        $this->searchResultFactory = $searchResultFactory;
        $this->filterGroup = $filterGroup;
        $this->filterBuilder = $filterBuilder;
        $this->orderAuthorization = $orderAuthorization;
        $this->orderRepository = $orderRepository;
        $this->collectionFactory = $collectionFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor;
    }

    /**
     * @inheritDoc
     */
    public function get($customerId, $id)
    {   /** @var Order $order */
        try {
            $order = $this->orderRepository->get($id);
            if ($this->orderAuthorization->canView($order, $customerId)) {
                return $order;
            } else {
                throw new LocalizedException(__('Cannot view the order'));
            }
        } catch (NoSuchEntityException $noSuchEntityException) {
            throw new NoSuchEntityException(__('Cannot load the order.'));
        }
    }

    /**
     * @inheritDoc
     */
    public function getList($customerId, SearchCriteriaInterface $searchCriteria)
    {
        $filterGroups = $searchCriteria->getFilterGroups();
        $this->filterGroup->setFilters([
            $this->filterBuilder
                ->setField('customer_id')
                ->setConditionType('eq')
                ->setValue($customerId)
                ->create()
        ]);
        $filterGroups = array_merge($filterGroups, [$this->filterGroup]);
        $searchCriteria->setFilterGroups($filterGroups);
        /** @var OrderSearchResultInterface $searchResult */
        $searchResult = $this->searchResultFactory->create();
        $searchResult->setSearchCriteria($searchCriteria);

        return $this->orderRepository->getList($searchCriteria);
    }
}
