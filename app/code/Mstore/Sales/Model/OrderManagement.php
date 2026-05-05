<?php declare(strict_types=1);

namespace Mstore\Sales\Model;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;
use Mstore\Sales\Api\OrderManagementInterface;

class OrderManagement implements OrderManagementInterface
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var OrderAuthorizationInterface
     */
    private $orderAuthorization;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var QuoteFactory
     */
    private $quoteFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * OrderManagement constructor.
     * @param StoreManagerInterface $storeManager
     * @param QuoteFactory $quoteFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param CartRepositoryInterface $quoteRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderAuthorizationInterface $orderAuthorization
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        QuoteFactory $quoteFactory,
        CustomerRepositoryInterface $customerRepository,
        CartRepositoryInterface $quoteRepository,
        OrderRepositoryInterface $orderRepository,
        OrderAuthorizationInterface $orderAuthorization
    ) {
        $this->storeManager = $storeManager;
        $this->quoteFactory = $quoteFactory;
        $this->quoteRepository = $quoteRepository;
        $this->customerRepository = $customerRepository;
        $this->orderAuthorization = $orderAuthorization;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @inheritDoc
     */
    public function cancel($id, $customerId)
    {
        /** @var Order $order */
        try {
            $order = $this->orderRepository->get($id);
            if ($this->orderAuthorization->canView($order, $customerId)) {
                if ($order->canCancel()) {
                    $order->cancel();
                    $this->orderRepository->save($order);
                    return true;
                }
            }
        } catch (NoSuchEntityException $noSuchEntityException) {
            throw new NoSuchEntityException(__('Cannot load the order.'));
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function reorder($id, $customerId)
    {
        /** @var Order $order */
        try {
            $order = $this->orderRepository->get($id);
            if ($this->orderAuthorization->canView($order, $customerId)) {
                $items = $order->getItemsCollection();
                $quote = $this->createCustomerCart($customerId);
                foreach ($items as $item) {
                    $quote->addProduct($item->getProduct(), $item->getBuyRequest());
                }
                $this->quoteRepository->save($quote);

                return true;
            }
        } catch (NoSuchEntityException $noSuchEntityException) {
            throw new NoSuchEntityException(__('Cannot load the order.'));
        } catch (LocalizedException $e) {
            throw new LocalizedException(__($e->getMessage()));
        }

        return false;
    }

    /**
     * Creates a cart for the currently logged-in customer.
     *
     * @param int $customerId
     * @param int $storeId
     * @return Quote Cart object.
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    protected function createCustomerCart($customerId, $storeId = null)
    {
        try {
            $quote = $this->quoteRepository->getActiveForCustomer($customerId);
        } catch (NoSuchEntityException $e) {
            if (null === $storeId) {
                $storeId = $this->storeManager->getStore()->getStoreId();
            }
            $customer = $this->customerRepository->getById($customerId);
            /** @var Quote $quote */
            $quote = $this->quoteFactory->create();
            $quote->setStoreId($storeId);
            $quote->setCustomer($customer);
            $quote->setCustomerIsGuest(0);
        }
        return $quote;
    }
}
