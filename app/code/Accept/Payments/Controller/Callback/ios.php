<?php
namespace Accept\Payments\Controller\Callback;

use Accept\Payments\Helper\Notify;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;

class ios extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface {

    public function __construct(
		\Magento\Framework\App\Action\Context $context, 
		\Magento\Sales\Model\Order $order,
		\Magento\Sales\Model\Service\InvoiceService $invoice,
		\Magento\Store\Model\StoreManagerInterface $storeManager,
		\Magento\Framework\App\ResourceConnection $resource,
		\Magento\Customer\Model\Customer $customer,
		\Accept\Payments\Model\ios $ios,
		\Magento\Sales\Model\Order\CreditmemoFactory $creditmemoFactory,
		\Magento\Sales\Model\Service\CreditmemoService $creditmemoService
    )
    {
			parent::__construct($context);
			$this->context = $context;
			$this->order = $order;
			$this->invoice = $invoice;
			$this->resultFactory = $context->getResultFactory();
			$this->base_url = $storeManager->getStore()->getBaseUrl();
			$this->website_id = $storeManager->getStore()->getWebsiteId();
			$this->resource = $resource;
			$this->customer = $customer;
			$this->ios = $ios;
			$this->creditmemoFactory = $creditmemoFactory;
			$this->creditmemoService = $creditmemoService;
	}
	
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
	}

    public function execute() {
		try{

			$ping = new Notify(
				$this->order,
				$this->invoice,
				$this->resultFactory,
				$this->resource,
				$this->customer,
				$this->messageManager,
				$this->base_url,
				$this->website_id,
				$this->ios->getConfigData('hmac_secret'),
				$this->creditmemoFactory,
				$this->creditmemoService
			);
			return $ping->Pong($this->getRequest()->getContent());
			
		}catch (\Exception $e){
			\var_dump($e);
			die(1);
		}

	}
}