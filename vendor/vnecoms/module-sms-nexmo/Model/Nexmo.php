<?php
namespace Vnecoms\SmsNexmo\Model;

use Vnecoms\Sms\Model\Sms;

class Nexmo implements \Vnecoms\Sms\Model\GatewayInterface
{
    /**
     * @var \Vnecoms\SmsNexmo\Helper\Data
     */
    protected $helper;
    
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    
    /**
     * @param \Vnecoms\SmsNexmo\Helper\Data $helper
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Vnecoms\SmsNexmo\Helper\Data $helper,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->helper = $helper;
        $this->logger = $logger;
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::getTitle()
     */
    public function getTitle()
    {
        return __("Nexmo");
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::validateConfig()
     */
    public function validateConfig()
    {
        $classExist = class_exists('Nexmo\Client');
        if(!$classExist){
            $this->logger->error(__("Class \Nexmo\Client does not exist. run 'composer require nexmo/client' to install Nexmo sdk."));
        }
        return $classExist &&
            $this->helper->getApiKey() &&
            $this->helper->getApiSecret();
    }

    /**
     * Send message
     *
     * @param string $number
     * @param string $message
     * @return array
     * @see \Vnecoms\Sms\Model\GatewayInterface::sendSms()
     */
    public function sendSms($number, $message)
    {
        $apiKey     = $this->helper->getApiKey();
        $apiSecret  = $this->helper->getApiSecret();

        $credentials = new \Nexmo\Client\Credentials\Basic($apiKey, $apiSecret);
        $client = new \Nexmo\Client($credentials);
        $sender = $this->helper->getSender();
        $note = '';
        $sId = '';
        $status = Sms::STATUS_FAILED;
        try{
            $params = [
                'from' => $sender?$sender:'Nexmo',
                'to' => $number,
                'text' => $message
            ];
            if($this->helper->isUnicode()){
                $params['type'] = 'unicode';
            }
            /**
             * @var \Nexmo\Message\Message $response
             */
            $response = $client->message()->send($params);
            $sId = $response->getMessageId();
            $status = $this->getMessageStatus($response);
        }catch(\Exception $e){
            $note = $e->getMessage();
        }
        $result = [
            'sid'       => $sId,
            'status'    => $status,
            'note'      => $note,
        ];

        return $result;
    }

    /**
     * Get Message Status
     *
     * @param \Nexmo\Message\Message $message
     * @return int
     * @see \Vnecoms\Sms\Model\GatewayInterface::getMessageStatus()
     */
    public function getMessageStatus($message)
    {
        $status = Sms::STATUS_FAILED;
        if ($message === null || !($message instanceof \Nexmo\Message\Message)) return $status;
        switch($message->getStatus()){
            case "0":
                $status = Sms::STATUS_SENT;
                break;
        }

        return $status;
    }

    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::getSms()
     */
    public function getSms($sid)
    {
        $apiKey     = $this->helper->getApiKey();
        $apiSecret  = $this->helper->getApiSecret();
        
        $credentials = new \Nexmo\Client\Credentials\Basic($apiKey, $apiSecret);
        $client = new \Nexmo\Client($credentials);
        
        $message = $client->message()->search($sid);
        return $message;
    }
}
