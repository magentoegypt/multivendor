<?php
namespace Vnecoms\SmsMobily\Model;

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
    function sendSMS($numbers, $sender, $msg, $MsgID, $timeSend=0, $dateSend=0, $deleteKey=0)
    {
        global $arraySendMsg;
        $url = "www.mobily.ws/api/msgSend.php";
        $sender = urlencode($sender);
        $domainName = $_SERVER['SERVER_NAME'];
        $stringToPost = "mobile=".$this->user."&password=".$this->pass."&numbers=".$numbers."&sender=".$sender."&msg=".$msg."&timeSend=".$timeSend."&dateSend=".$dateSend."&applicationType=".$this->appType."&domainName=".$domainName."&msgId=".$MsgID."&deleteKey=".$deleteKey."&lang=3";
        
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