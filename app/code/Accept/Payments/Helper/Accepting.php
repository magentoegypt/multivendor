<?php
namespace Accept\Payments\Helper;

class Accepting
{
    const ACCEPT_SERVER = "https://accept.paymobsolutions.com/api/";
    private $merchant;
    private $error;
    private $help;
    private $auth_token;
    private $shipping;
    private $billing;
    private $amount_cents;
    private $order_items;
    private $order_currency;
    private $order_unique_id;
    private $integration_id;
    private $payment_token;
    private $has_items;
    private $has_delivery;
    private $handles_shipping;
    private $cents;
    private $currency_rate;
    private $checkout_currency;

    public function __construct(
        \Magento\Sales\Model\Order $order,
        Array $config
    ) {
        $this->order                = $order;
        $this->auth_token           = $this->request_token($config['api_key']);
        $this->integration_id       = $config['integration_id'];
        $this->has_items            = $config['has_items'];
        $this->has_delivery         = $config['has_delivery'];
        $this->handles_shipping     = $config['handles_shipping'];
        if(isset($config['currency'])) $this->checkout_currency = $config['currency'];

        $this->set_order_data();

        $this->help = [
            "DEFAULT"  => [
                "Sorry, something went wrong please contact the store owner.",
            ],
            "PHONE"    => [
                "Wallet phone number must be a valid phone.",
            ],
        ];
    }

    public function convertPrice($price)
    {
        if($this->currency_rate==null) {
            if(!empty($this->checkout_currency)) {
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $this->currency_rate = $objectManager->create('Magento\Directory\Model\CurrencyFactory')->create()
                    ->load($this->order_currency)
                    ->getAnyRate($this->checkout_currency);
                $this->order_currency = $this->checkout_currency;
            } else {
                $this->currency_rate = 1;
            }
        }
        $convertedPrice = $price * $this->currency_rate;
        return round($convertedPrice);
    }

    public function valid_currency($gateway)
    {
		$valid = true;
		$online_currency = ["EGP", "USD", "EUR", "GBP"];
		$egp_currency = ["EGP"];

		switch ($gateway)
		{
			case "ONLINE":
				if(!in_array($this->order_currency, $online_currency)){
					$valid = false;
				}
				break;
            case "GETGO":
                if(!in_array($this->order_currency, $egp_currency)){
                    $valid = false;
                }
                break;

            case "Souhoola":
                if(!in_array($this->order_currency, $egp_currency)){
                    $valid = false;
                }
                break;
			case "VALU":
				if(!in_array($this->order_currency, $egp_currency)){
					$valid = false;
				}
				break;
			case "WALLET":
				if(!in_array($this->order_currency, $egp_currency)){
					$valid = false;
				}
				break;
			case "KIOSK":
				if(!in_array($this->order_currency, $egp_currency)){
					$valid = false;
				}
				break;
            case "premium":
                    if(!in_array($this->order_currency, $egp_currency)){
                        $valid = false;
                    }
                    break;
            case "installments":
                    if(!in_array($this->order_currency, $egp_currency)){
                        $valid = false;
                    }
                break;
            case "SYMPL":
                if(!in_array($this->order_currency, $egp_currency)){
                    $valid = false;
                }
                break;

            case "SHAHRY":
                if(!in_array($this->order_currency, $egp_currency)){
                    $valid = false;
                }
                break;
		}

		if(!$valid){
			$this->error = "Order currency is not allowed for this payment method ($gateway).";
		}

		return $valid;
    }

    public function get_token()
    {
        return $this->auth_token;
    }

    public function get_error()
    {
        $error = "";

        if (is_string($this->error) && $this->error != '')
        {
            $error = "<p>$this->error</p>";

        } else if (is_array($this->error) && !empty($this->error)) {
            foreach ($this->error as $text) {
                $error .= "<p>$text</p>";
            }
        } else {
            $error = "<p><code>ERROR_CODE: EMPTY_REASONS</code></p>";
        }

        return $error;
    }

    public function get_error_response($short_msg, $target_help)
    {
		$helpers = "";
        foreach ($this->help[$target_help] as $tip)
        {
            $helpers .= "<p>$tip</p>";
        }

        return "<p>$short_msg</p>" . $this->get_error() . $helpers;
    }

    public function request_token($api_key)
    {
        $data    = ['api_key' => $api_key];
        $result = $this->request('auth/tokens', $data);
        if ($result->token) {
            $this->merchant = $result->profile->id;
            return $result->token;
        } else {
            return false;
        }
    }

    public function register_order()
    {
        $data = [
            "delivery_needed"   => $this->has_delivery,
            "merchant_id"       => $this->merchant,
            "amount_cents"      => $this->amount_cents,
            "currency"          => $this->order_currency,
            "merchant_order_id" => $this->order_unique_id,
            "items"             => ($this->order_items) ? $this->order_items : [],
            "shipping_data"     => $this->shipping,
        ];
        return $this->request("ecommerce/orders", $data);
    }

    public function request_payment_key($registered_order_id, $saved_card = false)
    {
        $data = array(
            "amount_cents"   => $this->amount_cents,
            "expiration"     => 36000,
            "order_id"       => $registered_order_id,
            "billing_data"   => $this->billing,
            "currency"       => $this->order_currency,
            "integration_id" => $this->integration_id,
        );
        if ($saved_card) {$data['token'] = $saved_card;}

        $result = $this->request("acceptance/payment_keys", $data);

        if ($result) {
            $this->payment_token = $result->token;
            return $result->token;
        } else {
            return false;
        }
    }

    public function request_kiosk_id()
    {
        $source_data = array(
            "identifier" => 'AGGREGATOR',
            "subtype"    => 'AGGREGATOR',
        );
        $data = array(
            "source"        => $source_data,
            "billing"       => $this->billing,
            "payment_token" => $this->payment_token,
        );
        $result = $this->request("acceptance/payments/pay", $data);
        if ($result->pending) {
            if ($result->data->bill_reference) {
                return $result->data->bill_reference;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function request_wallet_url($phone_number)
    {
        $source_data = array(
            "identifier" => "$phone_number",
            "subtype"    => 'WALLET',
        );
        $data = array(
            "source"        => $source_data,
            "billing"       => $this->billing,
            "payment_token" => $this->payment_token,
        );
        $result = $this->request("acceptance/payments/pay", $data);

        if ($result) {
            return $result->redirect_url;
        } else {
            return false;
        }
    }

    private function request($command, $data = array(), $method = 'POST')
    {
        $ch  = curl_init();
        $url = self::ACCEPT_SERVER . $command;
        if ($this->auth_token) {$url .= "?token=" . $this->auth_token;}
        if ($method == 'GET') {
            $url .= '&' . http_build_query($data);
        } else {
            $data = json_encode($data);
        }
        $options = array(
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HEADER         => false,
            CURLOPT_HTTPHEADER     => array("Content-Type: application/json"),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL            => $url,
        );
        if ($method == 'POST') {
            $options[CURLOPT_POST]       = true;
            $options[CURLOPT_POSTFIELDS] = $data;
        }
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);

        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $this->error = curl_error($ch);
        curl_close($ch);
        $result = false;
        if (($code == 200 || $code == 201) && $response) {
            $result = json_decode($response);
            $error  = false;
        } else if ($code != 0) {
            if ($response) {
                $response    = json_decode($response, true);
                $error       = $this->errorParser($response);
                $this->error = $error;
            } else {
                $this->error = 'ErrorCode: ' . $code;
            }
        }
        return $result;
    }

    private function errorParser($array)
    {
        $result = '';
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                if ($key !== 0) {
                    $result .= $key . ': ';
                }
                if (is_array($value)) {
                    $result .= $this->errorParser($value) . "\n";
                } else {
                    $result .= $value;
                }
            }
        } else {
            $result = $array;
        }
        return $result;
    }

    public function set_order_data()
    {
        $this->order_unique_id = $this->order->getIncrementId() .'_'. time();
        $this->order_currency  = $this->order->getBaseCurrencyCode();

        if ($this->order_currency == "JOD")
        {
            $this->cents = 1000;
        }else {
            $this->cents = 100;
        }

        if( $this->handles_shipping )
        {
            $this->amount_cents = $this->order->getBaseGrandTotal() * $this->cents;
        }else{
            $shipping_fees = $this->order->getData()['base_shipping_amount'];
            $this->amount_cents = ($this->order->getBaseGrandTotal() - $shipping_fees) * $this->cents;
        }

        $this->amount_cents = $this->convertPrice($this->amount_cents);

        if($this->has_items)
        {
            foreach($this->order->getAllItems() as $item)
            {
                $this->order_items[] = [
                    "name" => $item->getName(),
                    "description" => ($item->getDescription()) ? $item->getDescription() : $item->getName(),
                    "quantity" => $item->getQtyOrdered(),
                    "amount_cents" => $this->convertPrice($item->getBasePrice() * $this->cents)
                ];
            }
		}

		$shipping_data = $this->order->getShippingAddress();
		$billing_data  = $this->order->getBillingAddress();

        $this->shipping = [
			"email"				=> ($this->order->getCustomerEmail()) ? $this->order->getCustomerEmail() : "NA",
			"first_name"		=> ($shipping_data->getFirstname()) ? $shipping_data->getFirstname() : "NA",
			"last_name"			=> ($shipping_data->getLastname()) ? $shipping_data->getLastname() : "NA",
			"phone_number"		=> ($shipping_data->getTelephone()) ? $shipping_data->getTelephone() : "NA",
			"street"			=> $shipping_data->getStreetLine(1).' - '.$shipping_data->getStreetLine(2),
			"country"			=> ($shipping_data->getCountryId()) ? $shipping_data->getCountryId() : "NA",
			"city"				=> ($shipping_data->getCity()) ? $shipping_data->getCity() : "NA",
			"postal_code"		=> ($shipping_data->getPostcode()) ? $shipping_data->getPostcode() : "NA",
			"state"				=> ($shipping_data->getRegion()) ? $shipping_data->getRegion() : "NA",
			"apartment"			=> "NA",
			"floor"				=> "NA",
			"building"			=> "NA",
			"shipping_method"	=> "UNK"
		];

        $this->billing = [
			"email"				=> $this->order->getCustomerEmail(),
			"first_name"		=> $billing_data->getFirstname(),
			"last_name"			=> $billing_data->getLastname(),
			"phone_number"		=> $billing_data->getTelephone(),
			"street"			=> $billing_data->getStreetLine(1).' - '.$billing_data->getStreetLine(2),
			"country"			=> $billing_data->getCountryId(),
			"city"				=> $billing_data->getCity(),
			"postal_code"		=> ($billing_data->getPostcode()) ? $billing_data->getPostcode() : "NA",
			"state"				=> ($billing_data->getRegion()) ? $billing_data->getRegion() : "NA",
			"apartment"			=> "NA",
			"floor"				=> "NA",
			"building"			=> "NA",
			"shipping_method"	=> "PKG"
		];
    }
}
