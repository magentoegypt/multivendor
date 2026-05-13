<?php
namespace Vnecoms\SmsLifetimesms\Http;


class Client
{
    const API_URL = 'http://admin.lifetimesms.com/json';

    /**
     * Token
     *
     * @var string
     */
    protected $token;

    /**
     * Secret
     *
     * @var string
     */
    protected $secret;

    /**
     * Client constructor.
     * @param string $token
     * @param string $secret
     */
    public function __construct($token, $secret)
    {
        $this->token    = $token;
        $this->secret   = $secret;
    }

	/**
     * Send SMS
     *
     * @param string $number
     * @param string $message
     */
    public function sendSms($number, $message, $sender){
		$data = [
			'api_token' => $this->token,
		    'api_secret' => $this->secret,
            'to' => $number,
            'from' => $sender,
		    'message' => $message,
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
		curl_setopt ( $ch, CURLOPT_TIMEOUT, 30 );
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
