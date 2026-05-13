<?php
namespace Vnecoms\SmsFastMobileSms\Rest;

class Client
{
    const API_URL = 'http://fastmobilesms.net/sendsms.php';
    
    /**
     * User name
     * 
     * @var string
     */
    protected $username;
    
    /**
     * Password
     *
     * @var string
     */
    protected $password;
    
    /**
     * @param string $username
     * @param string $password
     */
    public function __construct($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
    }
    
    /**
     * Send Sms
     * @param string $number
     * @param string $message
     * @param string $sender
     * @param string $isUnicode
     * @return string
     */
    public function sendSms($number, $message, $sender='FastMobile', $isUnicode=false){
        $postBody = $this->prepareParams($message, $number, $sender, $isUnicode);
        return $this->sendMessage($postBody);
    }
    
    /**
     * Send message
     * 
     * @param string $postBody
     * @param string $url
     * @return multitype:number string unknown mixed Ambigous <>
     */
    protected function sendMessage($postBody) {
        $ch = curl_init();
        curl_setopt ( $ch, CURLOPT_URL, self::API_URL );
        curl_setopt ( $ch, CURLOPT_POST, 1 );
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt ( $ch, CURLOPT_POSTFIELDS, $postBody );
        // Allowing cUrl funtions 20 second to execute
        curl_setopt ( $ch, CURLOPT_TIMEOUT, 20 );
        // Waiting 20 seconds while trying to connect
        curl_setopt ( $ch, CURLOPT_CONNECTTIMEOUT, 20 );
    
        $response = trim(curl_exec( $ch ));
        curl_close( $ch );
        return $response;
    }
    
    /**
     * Prepare Params
     * 
     * @param string $message
     * @param string $number
     * @param string $sender
     * @param bool $isUnicode
     * 
     * @return string
     */
    protected function prepareParams($message, $number, $sender, $isUnicode) {
        $postFields = array (
            'user'      => $this->username,
            'password'  => $this->password,
            'message'   => $message,
            'senderid'  => $sender,
            'mobile'    => $number
        );
        return $this->makePostBody($postFields);
    }
    
    /**
     * Make post body fields
     * 
     * @param array $postFields
     * @return string
     */
    protected function makePostBody($postFields) {
        $postBody = '';
        foreach( $postFields as $key => $value ) {
            $postBody .= urlencode( $key ).'='.urlencode( $value ).'&';
        }
        $postBody = rtrim( $postBody,'&' );
    
        return $postBody;
    }

    
    /**
     * Get message by message id
     * 
     * @param string $messageId
     * @throws \Exception
     * @return mixed
     */
    public function getMessage($messageId){
        throw new \Exception(__("Get message method is not supported"));
    }
}
