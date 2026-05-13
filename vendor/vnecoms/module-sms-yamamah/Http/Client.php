<?php
namespace Vnecoms\SmsYamamah\Http;

class Client
{
    const API_URL = 'http://api.yamamah.com/SendSMS';
    
    /**
     * @var string
     */
    protected $user;

    /**
     * @var string
     */
    protected $pass;
    

    /**
     * @param string $user
     * @param string $password
     */
    public function __construct($user, $password){
        $this->user = $user;
        $this->pass = $password;
    }
    
    /**
     * @param string $destination
     * @param string $text
     * @param string $sender
     * @return mixed
     */
    public function sendSms($destination, $text, $sender='') {
        $params = [
            'Username'  => $this->user,
            'Password' => $this->pass,
            'Tagname' => $sender,
            'Message' => $text,
            'RecepientNumber' => $destination,
            'SendDateTime' => 0,
            'EnableDR' => false,
        ];
        
        $headers = [
            'Content-type: application/json',
        ];
        
        $http = curl_init(self::API_URL);
        curl_setopt($http, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($http, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($http, CURLOPT_POST, true);
        curl_setopt($http, CURLOPT_POSTFIELDS, json_encode($params));
        $result = curl_exec($http);
        
        return $result;
    }
}
