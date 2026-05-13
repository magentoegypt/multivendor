<?php

namespace Vnecoms\VendorsCredit\Plugin;

use Magento\Directory\Model\Currency;
use Magento\Framework\Pricing\PriceCurrencyInterface;

class OrderGridCollection
{
    /**
     * @var PriceCurrencyInterface
     */
    protected $priceFormatter;

    /**
     * @var Currency|null
     */
    protected $currency;

    /**
     * OrderGridCollection constructor.
     * @param PriceCurrencyInterface $priceFormatter
     * @param Currency|null $currency
     */
    public function __construct(
        PriceCurrencyInterface $priceFormatter,
        Currency $currency = null
    ) {
        $this->priceFormatter = $priceFormatter;
        $this->currency = $currency ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->create(Currency::class);
    }

    /**
     * @return float
     */
    protected function getOrderCommission($orderId)
    {
        $om = \Magento\Framework\App\ObjectManager::getInstance();
        $order = $om->create('Magento\Sales\Model\Order')->load($orderId);
        $orderItemIds = $order->getItemsCollection()->getAllIds();

        $invoiceItemIds = [];
        $invoiceItemCollection = $om->create('Magento\Sales\Model\ResourceModel\Order\Invoice\Item\Collection');
        $invoiceItemCollection->addFieldToFilter('order_item_id', ['in' => $orderItemIds]);
        $invoiceItemIds = $invoiceItemCollection->getAllIds();

        $invoiceItems =[];
        foreach($invoiceItemIds as $invoiceItemId){
            $invoiceItems[] = 'invoice_item|'.$invoiceItemId;
        }

        $creditTransactionCollection = $om->create('Vnecoms\Credit\Model\ResourceModel\Credit\Transaction\Collection');
        $creditTransactionCollection->addFieldToFilter('additional_info',['in' => $invoiceItems]);
        $amount = 0;
        foreach($creditTransactionCollection as $trans){
            $amount += $trans->getAmount();
        }
        $baseCommission = abs($amount);
        return $baseCommission;
    }


    /**
     * @param \Magento\Sales\Model\ResourceModel\Order\Grid\Collection $subject
     * @param $result
     * @return mixed
     */
    public function afterGetItems(
        \Magento\Sales\Model\ResourceModel\Order\Grid\Collection $subject,
        $result
    ) {
        foreach($result as & $item){

            $orderId = $item['entity_id'];
            $amount = $this->getOrderCommission($orderId);

            $currencyCode = isset($item['base_currency_code']) ? $item['base_currency_code'] : null;
            $purchaseCurrency = $this->currency->load($currencyCode);
            $item['total_commission'] = $purchaseCurrency
                ->format($amount, [], false);

        }
        return $result;
    }

}
