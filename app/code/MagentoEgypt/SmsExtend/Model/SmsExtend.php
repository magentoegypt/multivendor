<?php
namespace MagentoEgypt\SmsExtend\Model;

use Vnecoms\Sms\Model\Sms;
use Magento\Framework\Exception\LocalizedException;

class SmsExtend
{
    /**
     * @var \MagentoEgypt\SmsExtend\Helper\Data
     */
    protected $helper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @param \MagentoEgypt\SmsExtend\Helper\Data $helper
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \MagentoEgypt\SmsExtend\Helper\Data $helper,
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
        return __("WhatsApp");
    }

    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::validateConfig()
     */
    public function validateConfig(){
        return $this->helper->getAccount() &&
            $this->helper->getVersion() &&
            $this->helper->getToken();
    }

    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::sendSms()
     */
    public function sendSms($number, $message){
        $template   = $this->helper->getTemplateName();
        // var_dump($template);die;
        if(empty($template)) {
            throw new LocalizedException(__("No template found"));
        }
        $account    = $this->helper->getAccount();
        $version    = $this->helper->getVersion();
        $token      = $this->helper->getToken();
        $lang       = $this->helper->getLang();
        $isOtp      = $this->helper->isOtpType();
        $vars       = $this->helper->getVars($message);
        // var_dump($vars,$template);die;

        $client = new \MagentoEgypt\SmsExtend\Http\Client($account, $version, $token, $lang);
        $response = $client->sendSms($number, $template, $vars, $isOtp);
        $responseArr = json_decode($response, true);
        // print_r($responseArr);die;
        $status = $this->getMessageStatus($responseArr);

        $result = [
            'sid'       => isset($responseArr['messages'][0]['id'])?$responseArr['messages'][0]['id']:'',
            'status'    => $status,
            'note'		=> isset($responseArr['remarks'])?$responseArr['remarks']:$response,
        ];

        return $result;
    }


    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::getMessageStatus()
     */
    public function getMessageStatus($response){
        if(isset($response['messages'][0]['message_status']) && $response['messages'][0]['message_status'] == 'accepted'){
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
