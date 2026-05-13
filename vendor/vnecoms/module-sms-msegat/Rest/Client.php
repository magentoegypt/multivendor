<?php
namespace Vnecoms\SmsMsegat\Rest;


class Client
{
    const API_URL = "https://www.msegat.com/gw/";
    
    /**
     * username
     * 
     * @var string
     */
    protected $username;
    
    /**
     * Api Key
     *
     * @var string
     */
    protected $apiKey;
    
    /**
     * @param string $username
     * @param string $password
     */
    public function __construct($username, $apiKey)
    {
        $this->username = $username;
        $this->apiKey = $apiKey;
    }
    
        /**
     * Send SMS
     * 
     * @param string $number
     * @param string $message
     * @param string $sender
     * @param boolean $isUnicode
     */
    public function sendSms($number, $message, $sender, $isUnicode = false){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::API_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);

        curl_setopt($ch, CURLOPT_POST, TRUE);
$postData = "--BOUNDARY
Content-Disposition: form-data; name=\"userName\"

".$this->username."
--BOUNDARY
Content-Disposition: form-data; name=\"apiKey\"

".$this->apiKey."
--BOUNDARY
Content-Disposition: form-data; name=\"numbers\"

".$number."
--BOUNDARY
Content-Disposition: form-data; name=\"userSender\"

".$sender."
--BOUNDARY
Content-Disposition: form-data; name=\"msg\"

".$message."
--BOUNDARY
Content-Disposition: form-data; name=\"msgEncoding\"

".($isUnicode?'UTF8':'windows-1256')."
--BOUNDARY
Content-Disposition: form-data; name=\"By\"

VNECOMS
--BOUNDARY--";
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: multipart/form-data; boundary=BOUNDARY"
        ));

        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
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
