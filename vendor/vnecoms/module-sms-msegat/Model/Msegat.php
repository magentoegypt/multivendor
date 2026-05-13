<?php
namespace Vnecoms\SmsMsegat\Model;

use Vnecoms\Sms\Model\Sms;
use Magento\Framework\Exception\LocalizedException;

class Msegat implements \Vnecoms\Sms\Model\GatewayInterface
{
    /**
     * @var \Vnecoms\SmsMsegat\Helper\Data
     */
    protected $helper;
    
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    
    /**
     * @param \Vnecoms\SmsMsegat\Helper\Data $helper
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Vnecoms\SmsMsegat\Helper\Data $helper,
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
        return __("www.msegat.com");
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::validateConfig()
     */
    public function validateConfig(){
        return
            $this->helper->getUsername() &&
            $this->helper->getApiKey() &&
            $this->helper->getSender();
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::sendSms()
     */
    public function sendSms($number, $message){
        $username   = $this->helper->getUsername();
        $apiKey   = $this->helper->getApiKey();
        $sender     = $this->helper->getSender();
        $isUnicode  = $this->helper->isUnicode();
        
        $client = new \Vnecoms\SmsMsegat\Rest\Client($username, $apiKey);
        $response = $client->sendSms($number, $message, $sender, $isUnicode);

        $result = [
            'sid'       => '',
            'status'    => $this->getMessageStatus($response),
            'note'		=> $this->getNote($response),
        ];

        return $result;
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::getMessageStatus()
     */
    public function getMessageStatus($response){
        $status = Sms::STATUS_FAILED;
		if($response === '1' || $response == 'M0000') $status = Sms::STATUS_SENT;
        return $status;
    }

    /**
     * @param $response
     * @return string
     */
    public function getNote($response){
        $note = $response;
        switch($response){
            case 'M0001':
            case '1010':
                $note = 'Variables missing';
                break;
            case 'M0002':
                $note = 'Invalid login info';
                break;
            case 'M0022':
                $note = 'Exceed number of senders allowed';
                break;
            case 'M0023':
                $note = 'Sender Name is active or under activation or refused';
                break;
            case 'M0024':
                $note = 'Sender Name should be in English or number';
                break;
            case 'M0025':
                $note = 'Invalid Sender Name Length';
                break;
            case 'M0026':
                $note = 'Sender Name is already activated or not found';
                break;
            case 'M0027':
                $note = 'Activation Code is not Correct';
                break;
            case 'M0028':
                $note = 'You reach maximum number of attempts. Sender name is locked';
                break;
            case '1020':
                $note = 'Invalid login info';
                break;
            case '1050':
                $note = 'MSG body is empty';
                break;
            case '1060':
                $note = 'Balance is not enough';
                break;
            case '1061':
                $note = 'MSG duplicated';
                break;
            case '1110':
                $note = 'Sender name is missing or incorrect';
                break;
            case '1120':
                $note = 'Mobile numbers is not correct';
                break;
            case '1140':
                $note = 'MSG length is too long';
                break;
            case 'M0029':
                $note = 'Invalid Sender Name - Sender Name should contain only letters, numbers and the maximum length should be 11 characters';
                break;
            case 'M0030':
                $note = 'Sender Name should ended with AD';
                break;
            case 'M0031':
                $note = 'Maximum allowed size of uploaded file is 5 MB';
                break;
            case 'M0032':
                $note = 'Only pdf,png,jpg and jpeg files are allowed!';
                break;
            case 'M0033':
                $note = 'Sender Type should be normal or whitelist only';
                break;
            case 'M0034':
                $note = 'Please Use POST Method';
                break;
        }

        return $note;
    }
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::getSms()
     */
    public function getSms($sid){
        throw new LocalizedException(__("This feature is not available"));
    }
}
