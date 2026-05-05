<?php
namespace Accept\Payments\Controller\Methods;

/**
 * Kiosk Payment Method
 */
use Accept\Payments\Helper\Accepting;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
class KioskMethod extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Sales\Model\Order $order,
        \Accept\Payments\Model\Kiosk $kiosk
    )
    {
        parent::__construct($context);
        $this->resultFactory = $context->getResultFactory();
        $this->order = $order;
        $this->kiosk = $kiosk;

        // Gateway unique options
        $this->api_id = "KIOSK";
        $this->api_has_iframe   = false;
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
                
                // config to be sent to the Accepting helper.
                $config = [
                    "api_key" => $this->kiosk->getConfigData('api_key'),
                    "integration_id" => $this->kiosk->getConfigData('integration_id'),
                    "has_iframe" => $this->api_has_iframe,
                    "has_items" => $this->api_has_items,
                    "handles_shipping" => $this->api_handles_shipping_fees,
                    "has_delivery" => $this->api_has_delivery,
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

            $kiosk_id = $helper->request_kiosk_id();

            if (!$kiosk_id) {
                throw new \Exception($helper->get_error_response("Can't obtain bill reference id.", "DEFAULT"));
            }

            $order->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT)
                  ->setStatus(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT)
                  ->addStatusHistoryComment( __("Order Created: Awaiting payment") )
                  ->save();

            $this->response->setData([
                'success' => true,
                'bill_reference' => $kiosk_id
            ]);

        } catch (\Exception $e) {
            $this->response->setData([
                'success' => false,
                'detail' => $e->getMessage(),
            ]);
        }

        return $this->response;
    }
}