<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Model;

use Vnecoms\RMA\Api\Data;
use Vnecoms\RMA\Api\MessageRepositoryInterface;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Vnecoms\RMA\Model\ResourceModel\Message as ResourceBlock;
use Vnecoms\RMA\Model\ResourceModel\Message\CollectionFactory as MessageCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class MessageRepository
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class MessageRepository implements MessageRepositoryInterface
{
    /**
     * @var ResourceBlock
     */
    protected $resource;

    /**
     * @var ResourceOperator
     */
    protected $resourceOperator;

    /**
     * @var MessageFactory
     */
    protected $messageFactory;

    /**
     * @var MessageCollectionFactory
     */
    protected $messageCollectionFactory;

    /**
     * @var Data\TicketSearchResultsInterfaceFactory
     */
    protected $searchResultsFactory;

    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var DataObjectProcessor
     */
    protected $dataObjectProcessor;

    /**
     * @var \Vnecoms\RMA\Api\Data\MessageFactory
     */
    protected $dataMessageFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var \Magento\Integration\Model\Oauth\Token
     */
    protected $_adminToken;
    /**
     * @var UserFactory
     */
    protected $operatorFactory;

    /**
     * @param ResourceBlock $resource
     * @param \Magento\User\Model\ResourceModel\User $resourceOperator
     * @param MessageFactory $messageFactory
     * @param \Vnecoms\RMA\Api\Data\MessageInterfaceFactory $dataMessageFactory
     * @param MessageCollectionFactory $messageCollectionFactory,
     * @param Data\MessageSearchResultsInterfaceFactory $searchResultsFactory,
     * @param DataObjectHelper $dataObjectHelper,
     * @param DataObjectProcessor $dataObjectProcessor,
     */
    public function __construct(
        ResourceBlock $resource,
        \Magento\User\Model\ResourceModel\User $resourceOperator,
        MessageFactory $messageFactory,
        \Vnecoms\RMA\Api\Data\MessageInterfaceFactory $dataMessageFactory,
        MessageCollectionFactory $messageCollectionFactory,
        Data\MessageSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Integration\Model\Oauth\Token $adminToken,
        \Magento\User\Model\UserFactory $operatorFactory
    ) {
        $this->resourceOperator = $resourceOperator;
        $this->resource = $resource;
        $this->messageFactory = $messageFactory;
        $this->messageCollectionFactory = $messageCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataMessageFactory = $dataMessageFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->storeManager = $storeManager;
        $this->_objectManager = $objectManager;
        $this->_adminToken = $adminToken;
        $this->operatorFactory = $operatorFactory;
    }

    /**
     * Save Message data
     *
     * @param \Vnecoms\RMA\Api\Data\MessageInterface $message
     * @param $token
     * @return \Vnecoms\RMA\Api\Data\MessageInterface
     * @throws CouldNotSaveException
     */
    public function save(Data\MessageInterface $message, $token, $requestId)
    {
        $tokenObject  = $this->_adminToken->loadByToken($token);
        if (!$tokenObject->getAdminId()) {
            throw new NoSuchEntityException(__('Token with id "%1" does not exist.', $token));
        }

        $operator = $this->operatorFactory->create();
        $this->resourceOperator->load($operator, $tokenObject->getAdminId());

        if (!$operator->getId()) {
            throw new NoSuchEntityException(__('Operator with id "%1" does not exist.', $tokenObject->getAdminId()));
        }

        $request = $this->_objectManager->create('Vnecoms\RMA\Model\Request')
            ->load($requestId);
        if (!$requestId || !$request->getId()) {
            throw new NoSuchEntityException(__('Request with id "%1" does not exist.', $requestId));
        }
        /*

        $message->setTicketId($ticket->getId());
        $message->setFrom($department->getTitle());
        $message->setIsShow(0);
        $message->setTo($ticket->getCustomerName());
        $message->setType(MESSAGE_TYPE::TYPE_REPLY_DEPARMENT);

        try {
            $this->resource->save($message);
            if(!$ticket->getOperatorId()){
                $ticket->setOperatorId($operator->getId());
            }
            $ticket->setStatus(TICKET_STATUS::STATUS_WAITING_CUSTOMER);
            $ticket->setData('total_replies',$ticket->getData('total_replies') + 1);
            $historyTime =array(
                'last_reply_time'=> $message->getCreatedAt() ,
                'last_department_reply_time'=> $message->getUpdatedAt(),
                'updated_time'=>$message->getUpdatedAt()
            );
            $ticket->createHistoryObject($historyTime);
            $statusHistoryData = array(
                "status" => $ticket->getStatus(),
                'change_by'=>  $department->getTitle(),
                'created_time'=> $message->getUpdatedAt(),
                'type' => TICKET_TYPE::CHANGE_BY_DEPARTMENT,
                'ticket_id' => $ticket->getId()
            );
            $ticket->createStatusHistoryObject($statusHistoryData);
            $ticket->save();
            $ticket->sendMailNotify(["type_send_mail"=>EMAIL_TYPE::REPLY_BY_DEPARTMENT],$message);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }
        */
        return $message;
    }

    /**
     * Load Message data by given Message Identity
     *
     * @param string $ticketId
     * @return Block
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($messageId)
    {
        $message = $this->messageFactory->create();
        $this->resource->load($message, $messageId);
        if (!$message->getId()) {
            throw new NoSuchEntityException(__('Message with id "%1" does not exist.', $messageId));
        }
        return $message;
    }

    /**
     * Load Ticket data collection by given search criteria
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @param \Magento\Framework\Api\SearchCriteriaInterface $criteria
     * @return \Vnecoms\RMA\Model\ResourceModel\Request\Collection
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $criteria, $requestId)
    {
        if (!$requestId) {
            throw new NoSuchEntityException(__('Request with id "%1" does not exist.', $requestId));
        }

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);

        $collection = $this->messageCollectionFactory->create();
        foreach ($criteria->getFilterGroups() as $filterGroup) {
            foreach ($filterGroup->getFilters() as $filter) {
                $condition = $filter->getConditionType() ?: 'eq';
                $collection->addFieldToFilter($filter->getField(), [$condition => $filter->getValue()]);
            }
        }

        $collection->addFieldToFilter('request_id', ["eq" => $requestId]);

        $searchResults->setTotalCount($collection->getSize());
        $sortOrders = $criteria->getSortOrders();
        if ($sortOrders) {
            foreach ($sortOrders as $sortOrder) {
                $collection->addOrder(
                    $sortOrder->getField(),
                    ($sortOrder->getDirection() == SortOrder::SORT_ASC) ? 'ASC' : 'DESC'
                );
            }
        }
        $collection->setCurPage($criteria->getCurrentPage());
        $collection->setPageSize($criteria->getPageSize());


        $messages = [];
        /** @var Message $messageModel */
        foreach ($collection as $messageModel) {
            $messageData = $this->dataMessageFactory->create();
            $this->dataObjectHelper->populateWithArray(
                $messageData,
                $messageModel->getData(),
                'Vnecoms\RMA\Api\Data\MessageInterface'
            );
            $messages[] = $this->dataObjectProcessor->buildOutputDataArray(
                $messageModel,
                'Vnecoms\RMA\Api\Data\MessageInterface'
            );
        }
        $searchResults->setItems($messages);
        return $searchResults;
    }
}
