<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Observer\Payment\Model\Cart;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Amasty\CheckoutCore\Model\ResourceModel\Fee\CollectionFactory as FeeCollectionFactory;

class CollectTotalsAndAmounts implements ObserverInterface
{
    /**
     * @var FeeCollectionFactory
     */
    protected $feeCollectionFactory;

    public function __construct(
        FeeCollectionFactory $feeCollectionFactory
    ) {
        $this->feeCollectionFactory = $feeCollectionFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(EventObserver $observer)
    {
        /** @var \Magento\Paypal\Model\Cart $cart */
        $cart = $observer->getCart();
        $id = $cart->getSalesModel()->getDataUsingMethod('entity_id');

        if (!$id) {
            $id = $cart->getSalesModel()->getDataUsingMethod('quote_id');
        }

        /** @var \Amasty\CheckoutCore\Model\ResourceModel\Fee\Collection $feesCollection */
        $feesCollection = $this->feeCollectionFactory->create()
            ->addFieldToFilter('quote_id', $id);

        $baseFeeAmount = 0;

        /** @var \Amasty\CheckoutCore\Model\Fee $fee */
        foreach ($feesCollection->getItems() as $fee) {
            $baseFeeAmount += $fee->getBaseAmount();
        }

        if ($feesCollection->getSize()) {
            $cart->addCustomItem(
                (string)__('Gift Wrap'),
                1,
                $baseFeeAmount
            );
        }
    }
}
