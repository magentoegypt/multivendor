<?php
namespace Vnecoms\SmsJawalbSms\Rest;

class Client
{
    const API_URL = 'https://www.jawalbsms.ws/api.php/sendsms';
    
    /**
     * username
     * 
     * @var string
     */
    protected $username;
    
    /**
     * password
     *
     * @var string
     */
    protected $password;
    
    /**
     * Create a new API client
     * 
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
    public function sendSms($number, $message, $sender, $isUnicode=false){
        $postBody = $isUnicode?
            $this->eightBitSms($message, $number, $sender):
            $this->unicodeSms($message, $number, $sender);
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
    
        $responseString = trim(curl_exec( $ch ));
        curl_close( $ch );
        $response = [
            'success'       => true,
            'message_id'    => '',
        ];
        $errors = [
            '-100' => __('Missing parameters (not exist or empty) Username + password.'),
            '-110' => __('Account not exist (wrong username or password).'),
            '-111' => __('The account not activated.'),
            '-112' => __('Blocked account.'),
            '-113' => __('Not enough balance.'),
            '-114' => __('The service not available for now.'),
            '-115' => __('The sender not available (if user have no opened sender).'),
            '-116' => __('Invalid sender name.'),
            '-120' => __('No destination addresses, or all destinations are not correct.'),
        ];
        if(isset($errors[$responseString])){
            $response['success'] = false;
            $response['msg'] = $errors[$responseString];
        }else{
            $response['success'] = true;
            $responseArr = explode("|", $responseString);
            foreach($responseArr as $tmpStr){
                $tmpStr = explode(':', $tmpStr);
                $response[strtolower(preg_replace('/[^A-Za-z0-9\-_]/', '',$tmpStr[0]))] = strtolower(trim($tmpStr[1]));
            }
        }
        return $response;
    }

    /**
     * Format Unicode Sms
     * 
     * @param string $message
     * @param string $msisdn
     * @return string
     */
    protected function unicodeSms ($message, $number, $sender) {
        $postFields = array (
            'user' => $this->username,
            'pass' => $this->password,
            'message'  => $message,
            'sender'  => $sender,
            'to'   => $number
        );
    
        return $this->makePostBody($postFields);
    }
    
    /**
     * Format 8bit sms
     * 
     * @param string $message
     * @param string $msisdn
     * @return string
     */
    protected function eightBitSms($message, $number, $sender) {
        $postFields = array (
            'user'      => $this->username,
            'pass'      => $this->password,
            'message'   => $message,
            'sender'    => $sender,
            'to'        => $number
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
     * Convert string to UTF16 Hex
     * 
     * @param string $string
     * @return string
     */
    protected function stringToUtf16Hex($string) {
        return bin2hex(mb_convert_encoding($string, "UTF-16", "UTF-8"));
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
