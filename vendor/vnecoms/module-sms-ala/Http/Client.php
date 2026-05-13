<?php
namespace Vnecoms\SmsAla\Http;


class Client
{
    const API_URL = 'http://api.smsala.com/api/SendSMS/';
    /**
     * API Key
     *
     * @var string
     */
    protected $apiKey;

    /**
     * API Pass
     *
     * @var string
     */
    protected $apiPass;

    /**
     * Client constructor.
     * @param string $apiKey
     * @param string $apiPass
     */
    public function __construct($apiKey, $apiPass)
    {
        $this->apiKey   = $apiKey;
        $this->apiPass  = $apiPass;
    }

    /**
     * @param string $number
     * @param string $message
     * @param string $sender
     * @param bool $isUnicode
     * @return multitype
     */
    public function sendSms($number, $message, $sender, $isUnicode = true){
		$data = [
            'api_id'        => $this->apiKey,
            'api_password'  => $this->apiPass,
            'sms_type'      => 'T',
            'encoding'      => $isUnicode?'U':'T',
            'sender_id'     => $sender,
            'phonenumber'   => $number,
            'textmessage'   => $message,
		];
        $result = $this->sendMessage($data);
        return $result;
    }


    /**
     * Send message
     *
     * @param string $params
     * @param string $url
     * @return multitype:number string unknown mixed Ambigous <>
     */
    protected function sendMessage($params) {
        $getBody = http_build_query($params);
		$ch = curl_init( );
		curl_setopt ( $ch, CURLOPT_URL, self::API_URL.'?'.$getBody );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt ( $ch, CURLOPT_TIMEOUT, 20 );
		curl_setopt ( $ch, CURLOPT_CONNECTTIMEOUT, 10 );
		$result = curl_exec( $ch );
		curl_close( $ch );
		return $result;
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
