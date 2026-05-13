<?php
namespace Vnecoms\SmsKapsystem\Model;

use Vnecoms\Sms\Model\Sms;
use GuzzleHttp\json_decode;

class Kapsystem implements \Vnecoms\Sms\Model\GatewayInterface
{
    /**
     * @var \Vnecoms\SmsKapsystem\Helper\Data
     */
    protected $helper;
    
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    
    /**
     * Error Codes
     * @var array
     */
    protected $errors = [
        '-1' => 'Error in processing the request',
        '-2' => 'Not enough credits on a specific account',
        '-3' => 'Targeted network is not covered on specific account',
        '-5' => 'Username or password is invalid',
        '-6' => 'Destination address is missing in the request',
        '-10' => 'Username is missing in the request',
        '-11' => 'Password is missing in the request',
        '-13' => 'Number is not recognized by the platform',
        '-22' => 'Incorrect XML format, caused by syntax error',
        '-23' => 'General error, reasons may vary',
        '-26' => 'General API error, reasons may vary',
        '-27' => 'Invalid scheduling parametar',
        '-28' => 'Invalid PushURL in the request',
        '-30' => 'Invalid APPID in the request',
        '-33' => 'Duplicated MessageID in the request',
        '-34' => 'Sender name is not allowed',
        '-99' => 'Error in processing request, reasons may vary',
    ];
    /**
     * @param \Vnecoms\SmsKapsystem\Helper\Data $helper
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Vnecoms\SmsKapsystem\Helper\Data $helper,
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
        return __("Kapsystem");
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::validateConfig()
     */
    public function validateConfig(){
        return
            $this->helper->getUsername() &&
            $this->helper->getPassword() &&
            $this->helper->getSender() &&
            $this->helper->getApiUrl();
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::sendSms()
     */
    public function sendSms($number, $message){
        $username   = $this->helper->getUsername();
        $password   = $this->helper->getPassword();
        $sender     = $this->helper->getSender();
        $apiUrl     = $this->helper->getApiUrl();
        
        $client = new \Vnecoms\SmsKapsystem\Rest\Client($username, $password);
        $response = $client->sendSms($number, $message, $sender, $apiUrl);
        $note = $response;
        $response = json_decode($response, true);
        
        $result = [
            'status'    => Sms::STATUS_FAILED,
            'note'      => $note,
        ];
        if($response && isset($response['results'])){
            $result['sid'] = $response['results'][0]['messageid'];
            $status = $response['results'][0]['status'];
            switch($status){
                case '0':
                    $result['status'] = Sms::STATUS_SENT;
                    break;
                default:
                    $result['note'] = (isset($this->errors[$status])?$this->errors[$status]:'').' | '.$result['note'];
                    break;
            }
        }

        return $result;
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::getMessageStatus()
     */
    public function getMessageStatus($message){
        $status = Sms::STATUS_FAILED;
    
        return $status;
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::getSms()
     */
    public function getSms($sid){
        throw new \Exception(__("this method is not supported"));
    }
}
