<?php
namespace Vnecoms\SmsUnifonic\Rest;

use GuzzleHttp\json_decode;
class Client
{
    const API_URL = 'https://el.cloud.unifonic.com/rest/SMS/messages';
    
    /**
     * App Id
     * 
     * @var string
     */
    protected $appId;
    
    protected $senderId;
    
    /**
     * Create a new API client
     * 
     * @param string $appId
     */
    public function __construct($appId, $senderId)
    {
        $this->appId = $appId;
        $this->senderId = $senderId;
    }
    
    /**
     * Send Sms
     * @param string $number
     * @param string $message
     * @param string $isUnicode
     * @return string
     */
    public function sendSms($number, $message, $isUnicode=false){
        $postBody = $this->prepareParams($message, $number, $isUnicode);
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
        $response = json_decode($responseString, true);
        return $response;
    }
    
    /**
     * Prepare Params
     * 
     * @param string $message
     * @param string $number
     * @param bool $isUnicode
     * 
     * @return string
     */
    protected function prepareParams($message, $number, $isUnicode) {
        $postFields = array (
            'AppSid'    => $this->appId,
            'Body'      => $message,
            'Recipient' => $number,
            'SenderID'  => $this->senderId
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
