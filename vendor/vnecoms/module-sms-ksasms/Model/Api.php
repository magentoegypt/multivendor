<?php
namespace Vnecoms\SmsKsaSms\Model;

class Api {
    /**
     * @var string
     */
    protected $user;
    
    /**
     * @var string
     */
    protected $pass;
    
    /**
     * @var string
     */
    protected $appType;
    
    /**
     * @param string $user
     * @param string $pass
     * @param number $appType
     */
    public function __construct($user, $pass, $appType=24){
        $this->user = $user;
        $this->pass = $pass;
        $this->appType = $appType;
    }
    
    //Send SMS API using CURL method
    function sendSMS($numbers, $sender, $msg, $unicode='e', $Rmduplicated=0, $return='json')
    {
        global $arraySendMsg;
        $url = "http://ksa-sms.com/api/sendsms.php";
        $sender = urlencode($sender);
        $stringToPost = "username=".$this->user."&password=".$this->pass."&numbers=".$numbers."&sender=".$sender."&message=".$msg."&unicode=".$unicode."&Rmduplicated=".$Rmduplicated."&return=".$return;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $stringToPost);
        $result = curl_exec($ch);
        
        return $result;
    }
}