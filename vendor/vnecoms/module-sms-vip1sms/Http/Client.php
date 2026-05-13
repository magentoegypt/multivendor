<?php
namespace Vnecoms\SmsVip1sms\Http;


class Client
{
    const API_URL = 'http://www.vip1sms.com/smartsms/api/sendsms.php';

    /**
     * Username
     *
     * @var string
     */
    protected $user;

    /**
     * Password
     *
     * @var string
     */
    protected $pass;

    /**
     * Client constructor.
     * @param string $user
     * @param string $pass
     */
    public function __construct($user, $pass)
    {
        $this->user = $user;
        $this->pass = $pass;
    }

	/**
     * Send SMS
     *
     * @param string $number
     * @param string $message
     */
    public function sendSms($number, $message, $sender, $isUnicode = true){
		$data = [
			'username' => $this->user,
		    'password' => $this->pass,
		    'message' => $message,
		    'numbers' => $number,
		    'sender' => $sender,
		    'unicode' => $isUnicode?'u':'e',
		    'return' => 'json',
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
}
