<?php
namespace Vnecoms\SmsUigtc\Http;


class Client
{
    const API_URL = 'https://kuwait.uigtc.com/capi/sms/send_sms';

    /**
     * API Key
     *
     * @var string
     */
    protected $api;

    /**
     * Client constructor.
     * @param $api
     */
    public function __construct($api)
    {
        $this->api = $api;
    }

	/**
     * Send SMS
     *
     * @param string $number
     * @param string $message
     */
    public function sendSms($number, $message, $sender){
        $number = str_replace('+','', $number);
		$data = [
			'api_key' => $this->api,
		    'sender_id' => $sender,
		    'send_type' => 1,
            'sms_content' => $message,
            'numbers' => $number,
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
        $postBody = http_build_query($params);

		$ch = curl_init( );
		curl_setopt ( $ch, CURLOPT_URL, self::API_URL );
		curl_setopt ( $ch, CURLOPT_POST, 1 );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt ( $ch, CURLOPT_POSTFIELDS, $postBody );
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

    /**
     * @return bool|string
     */
    public function getSenderIds(){
       $url = 'https://kuwait.uigtc.com/capi/cpanel/sender_ids?api_key='.$this->api;
        $ch = curl_init( );
        curl_setopt ( $ch, CURLOPT_URL, $url );
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt ( $ch, CURLOPT_TIMEOUT, 20 );
        curl_setopt ( $ch, CURLOPT_CONNECTTIMEOUT, 10 );
        $result = curl_exec( $ch );
        curl_close( $ch );
        return $result;
    }
}
