<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Observer\QuoteSubmit;

use Amasty\CheckoutCore\Api\AdditionalFieldsManagementInterface;
use Amasty\CheckoutCore\Api\Data\CustomFieldsConfigInterface;
use Amasty\CheckoutCore\Model\Config;
use Amasty\CheckoutCore\Model\FeeRepository;
use Amasty\CheckoutCore\Model\OrderCustomFieldsFactory;
use Amasty\CheckoutCore\Model\ResourceModel\OrderCustomFields;
use Amasty\CheckoutCore\Model\Subscription;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Api\Data\CartInterface;

/**
 * event sales_model_service_quote_submit_success
 */
class AfterSubmitObserver implements ObserverInterface
{
    /**
     * @var AdditionalFieldsManagementInterface
     */
    private $fieldsManagement;

    /**
     * @var Subscription
     */
    private $subscription;

    /**
     * @var FeeRepository
     */
    private $feeRepository;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var OrderCustomFieldsFactory
     */
    private $orderCustomFieldsFactory;

    /**
     * @var OrderCustomFields
     */
    private $orderCustomFieldsResource;

    public function __construct(
        AdditionalFieldsManagementInterface $fieldsManagement,
        Subscription $subscription,
        FeeRepository $feeRepository,
        Config $config,
        OrderCustomFieldsFactory $orderCustomFieldsFactory,
        OrderCustomFields $orderCustomFieldsResource
    ) {
        $this->fieldsManagement = $fieldsManagement;
        $this->subscription = $subscription;
        $this->feeRepository = $feeRepository;
        $this->config = $config;
        $this->orderCustomFieldsFactory = $orderCustomFieldsFactory;
        $this->orderCustomFieldsResource = $orderCustomFieldsResource;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(EventObserver $observer)
    {
        if (!$this->config->isEnabled()) {
            return $this;
        }
        /** @var  \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getEvent()->getQuote();
        if (!$order) {
            return $this;
        }

        $orderId = (int)$order->getId();
        $quoteId = (int)$quote->getId();

        $fee = $this->feeRepository->getByQuoteId($quoteId);
        if ($fee->getId()) {
            $fee->setOrderId($orderId);
            $this->feeRepository->save($fee);
        }

        $fields = $this->fieldsManagement->getByQuoteId($quoteId);

        $this->convertCustomFields($quote, $order);

        if (!$fields->getId()) {
            return $this;
        }

        if ($fields->getSubscribe()) {
            $this->subscription->subscribe($order->getCustomerEmail());
        }

        return $this;
    }

    /**
     * Convert Custom Fields from Quote to Order
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Sales\Model\Order $order
     */
    private function convertCustomFields(CartInterface $quote, \Magento\Sales\Model\Order $order): void
    {
        $orderId = $order->getId();
        $shipping = $quote->getShippingAddress();
        $billing = $quote->getBillingAddress();

        foreach (CustomFieldsConfigInterface::CUSTOM_FIELDS_ARRAY as $attributeCode) {
            /** @var \Amasty\CheckoutCore\Model\OrderCustomFields $orderCustomField */
            $orderCustomField = $this->orderCustomFieldsFactory->create(
                ['data' => ['name' => $attributeCode, 'order_id' => $orderId]]
            );
            $orderCustomField->setDataChanges(false);

            if ($shipping) {
                $attribute = $shipping->getData($attributeCode);
                if ($attribute) {
                    $orderCustomField->setShippingValue($attribute);
                }
            }

            $attribute = $billing->getData($attributeCode);
            if ($attribute) {
                $orderCustomField->setBillingValue($attribute);
            }

            if ($orderCustomField->hasDataChanges()) {
                $this->orderCustomFieldsResource->save($orderCustomField);
                $billing = $order->getBillingAddress();
                $shipping = $order->getShippingAddress();
                $billing->setCustomAttribute($attributeCode, $orderCustomField->getBillingValue());
                if ($shipping) {
                    $shipping->setCustomAttribute($attributeCode, $orderCustomField->getShippingValue());
                }
            }
        }
    }
}
