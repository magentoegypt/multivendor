<?php
namespace Vnecoms\Sms4jawaly\Model;

use Vnecoms\Sms\Model\Sms;
use Magento\Framework\Exception\LocalizedException;

class Sms4jawaly implements \Vnecoms\Sms\Model\GatewayInterface
{
    /**
     * @var \Vnecoms\Sms4jawaly\Helper\Data
     */
    protected $helper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @param \Vnecoms\Sms4jawaly\Helper\Data $helper
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Vnecoms\Sms4jawaly\Helper\Data $helper,
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
        return __("www.4jawaly.net");
    }

    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::validateConfig()
     */
    public function validateConfig(){
        return $this->helper->getUser() &&
            $this->helper->getPassword()&&
            $this->helper->getSender();
    }

    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::sendSms()
     */
    public function sendSms($number, $message){
        $user = $this->helper->getUser();
        $password = $this->helper->getPassword();
        $unicode = $this->helper->isUnicode();
        $sender = $this->helper->getSender();

        $client = new \Vnecoms\Sms4jawaly\Http\Client($user, $password);
        $response = $client->sendSms($number, $message, $sender, $unicode);
        $responseArr = json_decode($response, true);
        $note = '';
        if(!is_array($responseArr) || !isset($responseArr['Code'])){
            $status = Sms::STATUS_FAILED;
            $note = $response;
        }else{
            if($responseArr['Code'] == '100'){
                $status = Sms::STATUS_SENT;
                $note = __('numbers received successfully');
            }else{
                $errors = [
                    '101' => __('Incomplete data'),
                    '102' => __('incorrect user name'),
                    '103' => __('incorrect password'),
                    '104' => __('error in data base'),
                    '105' => __('credit not enough'),
                    '106' => __('sender name is invalid'),
                    '107' => __('sender name is blocked'),
                    '108' => __('ther aren\'t valid numbers to send'),
                    '109' => __('can\'t send to more than 8 parts'),
                    '110' => __('error in saving the sending results'),
                    '111' => __('sending is closed'),
                    '112' => __('the message contain blocked word'),
                    '113' => __('the account is inactive'),
                    '114' => __('the account is disabled'),
                    '115' => __('mobile not activated'),
                    '116' => __('email not activated'),
                    '117' => __('the message is empty , cannot be sent'),
                    '1010' => __('error in encryption'),
                    '1011' => __('user name not found'),
                    '1012' => __('pasword not found'),
                    '1013' => __('message text not found'),
                    '1014' => __('receiver number not found'),
                    '1015' => __('sender name is empty'),
                ];
                $status = Sms::STATUS_FAILED;
                $note = isset($errors[$responseArr['Code']])?$errors[$responseArr['Code']]:'ERROR';
            }
        }
        $result = [
            'sid'       => '',
            'status'    => $status,
            'note'		=> $note,
        ];

        return $result;
    }

    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Sms\Model\GatewayInterface::getMessageStatus()
     */
    public function getMessageStatus($response){
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
