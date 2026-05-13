<?php
namespace Vnecoms\SmsInfobip\Rest;

class Client
{
    const API_URL = 'https://api.infobip.com/sms/1/text/single';
    
    /**
     * @var string
     */
    protected $username;
    
    /**
     * @var string
     */
    protected $password;
    
    /**
     * @param string $username
     * @param string $password
     */
    public function __construct($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
    }
    
    /**
     * Send SMS
     * 
     * @param string $number
     * @param string $message
     * @param string $sender
     */
    public function sendSms($number, $message, $sender){
        $header = "Basic " . base64_encode($this->username . ":" . $this->password);
                
        // Prepare data for POST request
        $data = [
            'to'        => $number,
            "from"      => $sender,
            "text"      => $message,
        ];

        $curl = curl_init();
    
        curl_setopt_array($curl, array(
            CURLOPT_URL => self::API_URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                "accept: application/json",
                "authorization: ".$header,
                "content-type: application/json"
            ),
        ));
            
        $response = curl_exec($curl);
        $err = curl_error($curl);
        
        curl_close($curl);
        
        return $response;
    }
    
}
