<?php

namespace Mstore\Stripe\Model;

use Magento\Framework\App\Action\Context;
use Mstore\Stripe\Api\StripeInterface;
use Magento\Framework\Exception\LocalizedException;

class Stripe implements StripeInterface
{
    /**
     * @var Context
     */
    private $context;

    protected $dataFactory;


    public function __construct(
        Context $context
    ) {
        $this->context = $context;
        // $this->webhooksSetup = $webhooksSetup;
    }

    public function initStripe()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->webhooksSetup = $objectManager->get('\StripeIntegration\Payments\Helper\WebhooksSetup');
        if(!$this->webhooksSetup) return;
        
        $configurations = $this->webhooksSetup->getStoreViewAPIKeys();
        $processed = [];

        foreach ($configurations as $configuration)
        {
            $secretKey = $configuration['api_keys']['sk'];
            if (empty($secretKey))
                continue;

            if (in_array($secretKey, $processed))
                continue;

            $processed[$secretKey] = $secretKey;

            \Stripe\Stripe::setApiKey($secretKey);
        }
    }

    public function createPaymentIntent($payment_method_id, $email, $amount, $currencyCode, $captureMethod){
        $params = ["payment_method"=>$payment_method_id, "amount"=>$amount, "receipt_email"=>$email, "currency"=>$currencyCode, "capture_method"=>$captureMethod, "confirm"=>true, "description"=>"Payment from Magento for ".$email];
        try {
            $this->initStripe();
            
            $paymentIntent = \Stripe\PaymentIntent::create($params);
            if (!$paymentIntent || !isset($paymentIntent->id)){
                throw new LocalizedException(__('The payment intent with ID %1 could not be retrieved from Stripe', $payment_method_id));
            }
            return [['success'=>true, 'id'=> $paymentIntent->id, 'client_secret'=> $paymentIntent->client_secret]];
        } catch (\Exception $e) {
            throw new LocalizedException(__($e->getMessage()));
        }
    }

}