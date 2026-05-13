<?php
namespace Vnecoms\SmsTopGun\Http;

class Client
{
    const API_URL = "https://topsmsgun.com/topsmsgun-api/sms/direct/send";
    
    /**
     * @var string
     */
    protected $api;

    /**
     * @var string
     */
    protected $sender;

    /**
     * Client constructor.
     * @param $api
     * @param $sender
     */
    public function __construct($api, $sender){
        $this->api = $api;
        $this->sender = $sender;
    }
    
    /**
     * @param string $destination
     * @param string $text
     * @param string $sender
     * @param string $messageType
     * @return mixed
     */
    public function sendSms($destination, $text) {
        $postBody = [
            'campaignName' => $this->sender. " ". time(),
            'campaignText'  => $text,
            'contacts' => [
                [
                    "contactPhone" => $destination,
                    'contactName' => $destination,
                    'contactEmail' => ""
                ]
            ]
        ];
        return $this->_sendMessage($postBody);
    }
    
    /**
     * Send Message
     * 
     * @param array $postBody
     * @return mixed
     */
    protected function _sendMessage($postBody){
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => self::API_URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($postBody),
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_HTTPHEADER => [
                "Authorization: ".trim($this->api),
                "Content-type: application/json"
            ],
        ]);
        $response = curl_exec($ch);
        return $response;
    }
}
