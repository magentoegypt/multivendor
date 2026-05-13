<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsCredit\Model\CreditProcessor;

use Magento\Framework\Exception\LocalizedException;

class RefundSpentCredit extends \Vnecoms\Credit\Model\Processor\AbstractProcessor
{
    const TYPE = 'vendor_refund_spent_credit';

    protected $_action = 'add';

    /**
     * Url Builder
     *
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * RefundSpentCredit constructor.
     * @param \Vnecoms\Credit\Model\Credit\TransactionFactory $transactionFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Vnecoms\Credit\Helper\Data $helper
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Framework\UrlInterface $urlBuilder
     */
    public function __construct(
        \Vnecoms\Credit\Model\Credit\TransactionFactory $transactionFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Vnecoms\Credit\Helper\Data $helper,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\UrlInterface $urlBuilder
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->orderFactory = $orderFactory;
        parent::__construct($transactionFactory, $date, $localeDate, $helper);
    }

    /**
     * @see \Vnecoms\Credit\Model\Processor\AbstractProcessor::getTitle()
     */
    public function getTitle(){
        return __("Refund Spent Credit");
    }

    /**
     * @see \Vnecoms\Credit\Model\Processor\AbstractProcessor::getCode()
     */
    public function getCode(){
        return self::TYPE;
    }

    /**
     * Process data
     * @see \Vnecoms\Credit\Model\Processor\AbstractProcessor::process()
     */
    public function process($data=array()){
        if(!isset($data['amount']))
            throw new LocalizedException(__("Amout is not set in %1 on line %2", "<strong>".__FILE__."</strong>","<strong>".__LINE__."</strong>"));

        /*Process the credit amout*/
        $this->processAmount($data['amount']);

        $additionalInfo = 'vendor_order|'.$data['vendor_order']->getId();

        /*Create transasction*/
        $transData = [
            'customer_id'		=> $this->getCreditAccount()->getCustomerId(),
            'type'				=> self::TYPE,
            'amount'			=> $data['amount'],
            'balance'			=> $this->getCreditAccount()->getCredit(),
            'description'		=> __("Refund spent credit(s) from order #%1",$data['order']->getIncrementId()),
            'additional_info'	=> $additionalInfo,
            'created_at'		=> $this->date->timestamp(),
        ];
        $transaction = $this->transactionFactory->create();
        $transaction->setData($transData)->save();

        $this->sendNotificationEmail($transaction);
    }

    /**
     * Get Transaction Description
     * @see \Vnecoms\Credit\Model\Processor\AbstractProcessor::getDescription()
     */
    public function getDescription(\Vnecoms\Credit\Model\Credit\Transaction $transaction){
        $transactionData = $transaction->getAdditionalInfo();
        $transactionData = explode("|", $transactionData);
        if(sizeof($transactionData) < 2) return parent::getDescription($transaction);

        $order = $this->orderFactory->create()->load($transactionData[1]);
        if(!$order->getId()) return parent::getDescription($transaction);;

        $orderLink = '<a href="'.$this->urlBuilder->getUrl('sales/order/view',array('order_id'=>$order->getId())).'">'.$order->getIncrementId().'</a>';
        return __("Refund spent credit(s) from cancelled order #%1",$orderLink);
    }
}
