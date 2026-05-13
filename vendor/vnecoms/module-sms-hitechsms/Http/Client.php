<?php
namespace Vnecoms\SmsHitechSms\Http;


class Client
{
    const API_URL = 'https://sms.hitechsms.com/app/smsapi/index.php';

    /**
     * API Key
     *
     * @var string
     */
    protected $apiKey;

    /**
     * Client constructor.
     * @param $apiKey
     */
    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * @param string $number
     * @param string $message
     * @param string $sender
     * @param bool $isUnicode
     * @return multitype
     */
    public function sendSms($number, $message, $sender, $isUnicode = true){
        $number = str_replace("+91","", $number);

		$data = [
			'key' => $this->apiKey,
			'campaign' => '0',
			'routeid' => '13',
			'type' => $isUnicode?'text':'unicode',
            'contacts' => $number,
            'senderid' => $sender,
		    'msg' => $message,
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
