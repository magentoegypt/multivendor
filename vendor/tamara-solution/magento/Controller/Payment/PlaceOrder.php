<?php

declare(strict_types=1);

namespace Tamara\Checkout\Controller\Payment;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\NoSuchEntityException;

class PlaceOrder extends Action
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $magentoOrderRepository;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        JsonFactory $resultJsonFactory,
        CheckoutSession $checkoutSession,
        \Magento\Sales\Api\OrderRepositoryInterface $magentoOrderRepository
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->checkoutSession = $checkoutSession;
        $this->magentoOrderRepository = $magentoOrderRepository;
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $response = ['success' => true];
        $lastOrderId = $this->getRequest()->getParam('orderId', null);
        if (!$lastOrderId) {
            $lastOrderId = $this->getLastOrderId();
        }

        /**
         * @var \Magento\Payment\Model\Method\Logger $logger
         */
        $logger = $this->_objectManager->get('TamaraCheckoutLogger');
        try {
            $magentoOrder = $this->magentoOrderRepository->get($lastOrderId);
            if (substr($magentoOrder->getPayment()->getMethod(), 0, 6) == "tamara"
                && $magentoOrder->getState() == \Magento\Sales\Model\Order::STATE_NEW) {
                
                //only create checkout session here if needed
                /**
                 * @var \Tamara\Checkout\Api\CheckoutInformationRepositoryInterface $checkoutInformationRepository
                 */
                $checkoutInformationRepository = $this->_objectManager->get(
                    \Tamara\Checkout\Api\CheckoutInformationRepositoryInterface::class
                );
                $checkoutInfo = $checkoutInformationRepository->getTamaraCheckoutInformation($lastOrderId);
                
                if (!$checkoutInfo) {
                    throw new \Exception('Could not create or retrieve Tamara checkout session. Please try again.');
                }
                
                $response['orderId'] = $lastOrderId;
                $response['redirectUrl'] = $checkoutInfo->getRedirectUrl();
            } else {
                throw new NoSuchEntityException(__('Requested order doesn\'t exist'));
            }
        } catch (\Exception $exception) {
            $response['success'] = false;
            $logger->debug(['Tamara - Error when retrieving or creating tamara checkout session' => $exception->getMessage()], null, true);
            $logger->debug(['Tamara - Exception trace' => $exception->getTraceAsString()], null, true);
            $response['error'] = $exception->getMessage();
        }
        return $result->setData($response);
    }

    private function getLastOrderId()
    {
        return $this->checkoutSession->getLastOrderId();
    }
}
