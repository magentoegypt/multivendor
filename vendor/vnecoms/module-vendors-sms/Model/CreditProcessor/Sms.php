<?php

namespace Vnecoms\VendorsSms\Model\CreditProcessor;

use Magento\Framework\Exception\LocalizedException;

class Sms extends \Vnecoms\Credit\Model\Processor\AbstractProcessor
{
    const TYPE = 'buy_sms_credit';
    
    protected $_action = 'subtract';
        
    /**
     * @see \Vnecoms\Credit\Model\Processor\AbstractProcessor::getTitle()
     */
    public function getTitle()
    {
        return __("Buy SMS Credit");
    }
    
    /**
     * @see \Vnecoms\Credit\Model\Processor\AbstractProcessor::getCode()
     */
    public function getCode()
    {
        return self::TYPE;
    }
    
    /**
     * Process data
     * @see \Vnecoms\Credit\Model\Processor\AbstractProcessor::process()
     */
    public function process($data = [])
    {
        if (!isset($data['amount'])) {
            throw new LocalizedException(__("Amout is not set in %1 on line %2", "<strong>".__FILE__."</strong>", "<strong>".__LINE__."</strong>"));
        }

        /*Process the credit amout*/
        $this->processAmount($data['amount']);

        
        /*Create transasction*/
        $transData = [
            'customer_id'       => $this->getCreditAccount()->getCustomerId(),
            'type'              => self::TYPE,
            'amount'            => -$data['amount'],
            'balance'           => $this->getCreditAccount()->getCredit(),
            'description'       => __("Buy SMS Credit"),
            'additional_info'   => null,
            'created_at'        => $this->date->timestamp(),
        ];
        $transaction = $this->transactionFactory->create();
        $transaction->setData($transData)->save();
        
        $this->sendNotificationEmail($transaction);
    }
}
