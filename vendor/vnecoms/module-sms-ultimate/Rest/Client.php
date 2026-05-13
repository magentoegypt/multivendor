<?php
namespace Vnecoms\SmsUltimate\Rest;

class Client
{
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

    public function setApiKey($apiKey) {
        $this->apiKey = $apiKey;
    }

    public function setApiUrl($url) {
        $this->apiUrl = $url;
    }

    /**
     * @param $data
     * @return bool|string
     */
    public function sendSms($data){
        $apiKey     = trim($this->apiKey);
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
                "Authorization: Bearer ".$apiKey,
                'Content-Type: application/json',
                'Accept: application/json'
            ],
        ]);
        $response = curl_exec($ch);
        return $response;
    }

}
