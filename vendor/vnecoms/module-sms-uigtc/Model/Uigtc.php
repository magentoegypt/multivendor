<?php
namespace Vnecoms\SmsUigtc\Model;

use Vnecoms\Sms\Model\Sms;
use Magento\Framework\Exception\LocalizedException;

class Uigtc implements \Vnecoms\Sms\Model\GatewayInterface
{
    /**
     * @var \Vnecoms\SmsUigtc\Helper\Data
     */
    protected $helper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @param \Vnecoms\SmsUigtc\Helper\Data $helper
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Vnecoms\SmsUigtc\Helper\Data $helper,
        \Psr\Log\LoggerInterface $logger
    ){
        $this->helper = $helper;
        $this->logger = $logger;
    }

    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::getTitle()
     */
    public function getTitle(){
        return __("uigtc.com");
    }

    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::validateConfig()
     */
    public function validateConfig(){
        return $this->helper->getApiKey() &&
            $this->helper->getSender();
    }

    /**
     * @return bool|string
     */
    public function getSenderIds(){
        $apiKey     = $this->helper->getApiKey();
        $client     = new \Vnecoms\SmsUigtc\Http\Client($apiKey);
        $senderIds  = $client->getSenderIds();
        $senderIds = json_decode($senderIds, true);
        if(!is_array($senderIds)) return ['value'=>'', 'label'=> __('No sender available')];
        $result = [];
        if(isset($senderIds['error'])) return ['value'=>'', 'label'=> __('No sender available')];
        
        foreach($senderIds as $sender){
            $result[] = [
                'label' => $sender['senderid'],
                'value' => $sender['id'],
            ];
        }
        return $result;
    }


    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::sendSms()
     */
    public function sendSms($number, $message){
        $apiKey   = $this->helper->getApiKey();
        $sender = $this->helper->getSender();

        $client = new \Vnecoms\SmsUigtc\Http\Client($apiKey);
        $response = $client->sendSms($number, $message, $sender);
		$responseArr = json_decode($response, true);
		if(isset($responseArr['success'])){
            $result = [
                'status'    => Sms::STATUS_SENT,
                'note'		=> $responseArr['success'],
            ];
        }elseif(isset($responseArr['error'])){
            $result = [
                'status'    => Sms::STATUS_FAILED,
                'note'		=> $responseArr['error'],
            ];
        }else{
            $result = [
                'status'    => Sms::STATUS_FAILED,
                'note'		=> $response,
            ];
        }

        return $result;
    }

    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::getMessageStatus()
     */
    public function getMessageStatus($response){
		return Sms::STATUS_SENT;
    }

    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::getSms()
     */
    public function getSms($sid){
        throw new LocalizedException(__("This feature is not available"));
    }
}
