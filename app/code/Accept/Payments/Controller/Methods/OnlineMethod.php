<?php
namespace Accept\Payments\Controller\Methods;

/**
 * Online Payment Method
 */
use Accept\Payments\Helper\Accepting;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
class OnlineMethod extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Sales\Model\Order $order,
        \Magento\Framework\App\ResourceConnection $resource,
        \Accept\Payments\Model\Online $online
    )
    {
        parent::__construct($context);
        $this->resultFactory = $context->getResultFactory();
        $this->order = $order;
        $this->resource = $resource;
        $this->online = $online;

        // Gateway unique options
        $this->api_id = "ONLINE";
        $this->api_has_iframe   = true;
        $this->api_has_items    = false;
        $this->api_has_delivery = false;
        $this->api_handles_shipping_fees = true;
    }
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
	}
    public function execute()
    {
        $this->response = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON);
        $this->response->setHttpResponseCode(200);
        $order_id = $this->getRequest()->getContent();

        try{
            $order = $this->order->load($order_id);
            if($order){

                $iframe_id = $this->online->getConfigData('iframe_id');

                // config to be sent to the Accepting helper.
                $config = [
                    "api_key" => $this->online->getConfigData('api_key'),
                    "integration_id" => $this->online->getConfigData('integration_id'),
                    "has_iframe" => $this->api_has_iframe,
                    "has_items" => $this->api_has_items,
                    "handles_shipping" => $this->api_handles_shipping_fees,
                    "has_delivery" => $this->api_has_delivery,
                    "currency" => $this->online->getConfigData('currency'),
                ];

                // Start a helper instance
                $helper = new Accepting($order, $config);

            }else {
                throw new \Exception("<p><b>Fatal Error:</b> Order with the id of ($order_id) was not found!</p>");
            }

            if (!$helper->valid_currency($this->api_id)) {
                throw new \Exception($helper->get_error_response("Store currency is not supported by this payment method.", "DEFAULT"));
            }

            if (!$helper->get_token()) {
                throw new \Exception($helper->get_error_response("Can't obtain auth token.", "DEFAULT"));
            }

            $registered_order = $helper->register_order();

            if (!$registered_order->id) {
                throw new \Exception($helper->get_error_response("Can't register order.", "DEFAULT"));
            }

            $payment_key = $helper->request_payment_key($registered_order->id);

            if (!$payment_key) {
                throw new \Exception($helper->get_error_response("Can't obtain payment key.", "DEFAULT"));
            }

            $iframe_url = "https://accept.paymobsolutions.com/api/acceptance/iframes/$iframe_id?payment_token=$payment_key";

            if( $order->getCustomerId() )
            {
                $customer_id = $order->getCustomerId();
                $connection = $this->resource->getConnection();
                $tableName = $this->resource->getTableName('Accept_Payments_Tokens');
                $cards = $connection->fetchAll("SELECT * FROM $tableName WHERE `customer_id` = '$customer_id'");

                // customer has cards stored?
                if($cards)
                {
                    foreach ($cards as $index => $card)
                    {
                        $payment_keys[$index] = $helper->request_payment_key($registered_order->id, $card['token']);
                        if($payment_keys[$index])
                        {
                            $saved_card[$index]['url']          = "https://accept.paymobsolutions.com/api/acceptance/iframes/$iframe_id?payment_token=$payment_keys[$index]";
                            $saved_card[$index]['masked_pan']   = $card['masked_pan'];
                            $saved_card[$index]['card_subtype'] = $card['card_subtype'];
                        }
                    }
                }
            }

            $order->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT)
                  ->setStatus(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT)
                  ->addStatusHistoryComment( __("Order Created: Awaiting payment") )
                  ->save();

            $this->response->setData([
                'success' => true,
                'iframe_url' => $iframe_url,
                'owner' => null,
                'cards' => null
            ]);

            if( isset($saved_card) ){
                $this->response->setData([
                    'success' => true,
                    'iframe_url' => $iframe_url,
                    'owner' => $order->getCustomerName(),
                    'cards' => $saved_card
                ]);
            }

        } catch (\Exception $e) {
            $this->response->setData([
                'success' => false,
                'detail' => $e->getMessage(),
            ]);
        }

        return $this->response;
    }
}
