<?php
namespace MagentoEgypt\SmsExtend\Http;

class Client
{
    const API_URL = 'https://graph.facebook.com/';
    /**
     * @var string
     */
    protected $accout;

    /**
     * @var string
     */
    protected $version;

    /**
     * @var string
     */
    protected $token;

    /**
     * @var string
     */
    protected $lang;

    /**
     * Client constructor.
     * @param string $accout
     * @param string $version
     * @param string $token
     * @param string $lang
     */
    public function __construct($accout, $version, $token, $lang)
    {
        $this->accout   = $accout;
        $this->version  = $version;
        $this->token  = $token;
        $this->lang  = $lang ?? 'en';
    }

    /**
     * @param string $number
     * @param string $message
     * @param array[] $variable
     * @return multitype
     */
    public function sendSms($number, $message, $variable = [], $isOtp = false)
    {
        if(!$isOtp) {
            $parameters = [];
            $variable = array_filter($variable);
            foreach($variable as $var) {
                $parameters[] = ["type"=> "text","text"=> $var];
            }
            $data = [
                "messaging_product" => "whatsapp",
                "recipient_type" => "individual",
                "to" => $number,
                "type" => "template",
                "template" => [
                    "name" => $message,
                    "language" => [
                        "code" => $this->lang
                    ],
                    "components" => [
                        [
                            "type" => "body",
                            "parameters" => $parameters
                        ]
                    ]
                ]
            ];
        } else {
            $variable = array_values($variable);
            $data = [
                "messaging_product" => "whatsapp",
                "recipient_type" => "individual",
                "to" => $number,
                "type" => "template",
                "template" => [
                    "name" => $message,
                    "language" => [
                        "code" => $this->lang
                    ],
                    "components" => [
                        [
                            "type" => "body",
                            "parameters" => [
                                ["type"=> "text","text"=> (string) $variable[0]]
                            ]
                        ],
                        [
                            "type" => "button",
                            "sub_type" => "url",
                            "index" => "0",
                            "parameters" => [
                                ["type"=> "text","text"=> (string) $variable[0]]
                            ]
                        ]
                    ]
                ]
            ];
        }
        // print_r($data);die;
        $result = $this->sendMessage('messages', $data);
        return $result;
    }


    /**
     * Send message
     *
     * @param string $url
     * @param string $params
     * @param string $method
     * @return multitype:number string unknown mixed Ambigous <>
     */
    protected function sendMessage($url, $params, $method = 'POST') {
        $getBody = http_build_query($params);
		$curl = curl_init();
		curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://graph.facebook.com/'.$this->version.'/'.$this->accout.'/'.$url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => json_encode($params),
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer '.$this->token,
                'Content-Type: application/json'
            ),
        ));
		$result = curl_exec( $curl );
		curl_close( $curl );
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
