<?php
namespace Vnecoms\SmsMsg91\Rest;

class Client
{
    const API_URL = 'https://api.msg91.com/api/v2/sendsms';
    const WORLD_API_URL = 'https://world.msg91.com/api/v2/sendsms';

    /**
     * @var string
     */
    protected $apiUrl;

    /**
     * Msg91 API Key
     *
     * @var string
     */
    protected $apiKey;
    
    /**
     * Create a new API client
     * 
     * @param string $apiKey
     */
    public function __construct($apiUrl, $apiKey)
    {
        $this->apiKey = $apiKey;
        $this->apiUrl = $apiUrl;
    }
    
    /**
     * Send SMS
     *
     * @param string $number
     * @param string $message
     * @param string $sender
     * @param string $unicode
     */
    public function sendSms($number, $message, $sender, $dlt = null,  $unicode = '0'){
        $apiKey     = $this->apiKey;
        $country = 0;
        if(substr($number, 0,1) === "1"){
            $country = "1";
        }elseif(substr($number, 0,2) === "91"){
            $country = "91";
        }elseif(substr($number, 0,2) === "96"){
            $country = "0096";
        }
        // Prepare data for POST request
        $data = [
            'sender'    => $sender,
            'country'   => "0",
            "route"     => "4",
            "sms"       => [
                ["message" => $message, "to" => [$number]]
            ]
        ];

        if ($dlt) {
            $data["DLT_TE_ID"] = $dlt;
        }
		
		if($unicode){
            $data['unicode'] = '1';
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
          CURLOPT_URL => $this->apiUrl,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => json_encode($data),
          CURLOPT_SSL_VERIFYHOST => 0,
          CURLOPT_SSL_VERIFYPEER => 0,
          CURLOPT_HTTPHEADER => [
            "authkey: $apiKey",
            "content-type: application/json"
          ],
        ]);
        $response = curl_exec($ch);
        return $response;
    }
    
}
