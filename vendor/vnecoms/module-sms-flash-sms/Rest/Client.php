<?php
namespace Vnecoms\SmsFlashSms\Rest;

class Client
{
    const API_URL = 'http://www.flashsms.net/api/sendsms.php';
    
    /**
     * Flash sms user name
     *
     * @var string
     */
    protected $user;
	
	/**
     * Flash sms pass
     *
     * @var string
     */
    protected $pass;
    
    /**
     * Create a new API client
     * 
     * @param string $user
	 * @param string $pass
     */
    public function __construct($user, $pass)
    {
        $this->user = $user;
        $this->pass = $pass;
    }
    
    /**
     * Send SMS
     * 
     * @param string $number
     * @param string $message
     * @param string $sender
     */
    public function sendSms($number, $message, $sender, $unicode='e', $Rmduplicated=1, $return = 'json'){
        $user     	= urlencode($this->user);
        $pass     	= urlencode($this->pass);
        $message    = rawurlencode($message);
        $sender     = urlencode($sender);
                
        // Prepare data for POST request
	/* arabic should not add unicode in data */
        $data = [
            'username'    => $user,
            'password'    => $pass,
            'numbers'   => $number,
            "sender"    => $sender,
            "message"   => $message,
            "Rmduplicated"   	=> $Rmduplicated,
            "return"   			=> $return
        ];

        // Send the POST request with cURL
        $ch = curl_init(self::API_URL);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        
        return $response;
    }
    
}
