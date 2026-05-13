<?php
namespace Vnecoms\SmsWeb2sms\Model;

use Magento\Framework\HTTP\AsyncClient\Request;
use Magento\Framework\HTTP\AsyncClientInterface;
use Vnecoms\Sms\Model\Sms;
use Magento\Framework\Exception\LocalizedException;

class Web2sms implements \Vnecoms\Sms\Model\GatewayInterface
{
    const API_URL = 'https://e3len.vodafone.com.eg/web2sms/sms/submit/';
    /**
     * @var \Vnecoms\Web2sms\Helper\Data
     */
    protected $helper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;


    /**
     * @var \Magento\Framework\HTTP\ZendClientFactory
     * @deprecated Use asynchronous client.
     * @see $httpClient
     */
    protected $_httpClientFactory;

    /**
     * @var \Magento\Shipping\Model\Simplexml\ElementFactory $xmlElFactory
     */
    protected $_xmlElFactory;

    /**
     * Web2sms constructor.
     * @param \Vnecoms\Web2sms\Helper\Data $helper
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Shipping\Model\Simplexml\ElementFactory $xmlElFactory
     * @param \Magento\Framework\HTTP\ZendClientFactory $httpClientFactory
     */
    public function __construct(
        \Vnecoms\SmsWeb2sms\Helper\Data $helper,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Shipping\Model\Simplexml\ElementFactory $xmlElFactory,
        \Magento\Framework\HTTP\ZendClientFactory $httpClientFactory
    ){
        $this->helper = $helper;
        $this->logger = $logger;
        $this->_xmlElFactory = $xmlElFactory;
        $this->_httpClientFactory = $httpClientFactory;
    }

    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::getTitle()
     */
    public function getTitle(){
        return __("Web2sms");
    }

    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::validateConfig()
     */
    public function validateConfig(){
        return $this->helper->getAccountId() &&
            $this->helper->getPassword()&&
            $this->helper->getSender();
            $this->helper->getSecretKey();
    }

    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::sendSms()
     */
    public function sendSms($number, $message){
        $user = $this->helper->getAccountId();
        $password = $this->helper->getPassword();
        $secretKey = $this->helper->getSecretKey();
        $sender = $this->helper->getSender();
        $xmlStr = '<?xml version="1.0" encoding="UTF-8"?>
                  <SubmitSMSRequest xmlns="http://www.edafa.com/web2sms/sms/model/">
                      <AccountId>'.$user.'</AccountId>
                      <Password>'.$password.'</Password>
                      <SecureHash>'.hash_hmac ('sha256',$secretKey, 'secret') .'</SecureHash>
                      <SMSList>
                        <SenderName>'.$sender.'</SenderName>
                        <ReceiverMSISDN>'.$number.'</ReceiverMSISDN>
                        <SMSText>'.$message.'</SMSText>
                      </SMSList>
                  </SubmitSMSRequest>';
        $headers = [];
        $headers[] = 'Content-type: application/xml';
        $client = $this->_httpClientFactory->create();
        $client->setUri(self::API_URL);
        $client->setHeaders($headers);
        $client->setConfig(['maxredirects' => 0, 'timeout' => 30]);
        $client->setRawData($xmlStr);
        $responseBody = $client->request(\Zend_Http_Client::POST)->getBody();
        $bodyXml = $this->_xmlElFactory->create(['data' => $responseBody]);
        $note = isset($bodyXml->Description) ? $bodyXml->Description->asArray() : null;
        $result = [
            'sid'       => '',
            'status'    => $this->getMessageStatus($bodyXml),
            'note'		=> $note
        ];
        return $result;
    }

    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::getMessageStatus()
     */
    public function getMessageStatus($response){
        if(isset($response->ResultStatus) && $response->ResultStatus->asArray() == 'SUCCESS'){
            return Sms::STATUS_SENT;
        }
        return Sms::STATUS_FAILED;
    }

    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::getSms()
     */
    public function getSms($sid){
        throw new LocalizedException(__("This feature is not available"));
    }
}
