<?php
namespace Vnecoms\SmsHTD\Http;

class Client
{
    const API_URL = "https://sms.htd.ps/API/SendSMS.aspx";

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
            'id' =>  $this->api,
            'sender' => $this->sender,
            'to' => $destination,
            'msg' => $text,
            'mode' => 1
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
        $fieldString = '';
        foreach($postBody as $key=>$value){
            $fieldString .= urlencode($key).'='.urlencode($value)."&";
        }
        $fieldString = trim($fieldString, '&');
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => self::API_URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $fieldString,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0
        ]);

        $response = curl_exec($ch);
        return $response;
    }
}
