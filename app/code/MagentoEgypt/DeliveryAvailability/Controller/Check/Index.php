<?php
declare(strict_types=1);
namespace MagentoEgypt\DeliveryAvailability\Controller\Check;
class Index implements \Magento\Framework\App\Action\HttpGetActionInterface
{
    public function __construct(private \Magento\Framework\App\RequestInterface $request,
        private \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        private \MagentoEgypt\DeliveryAvailability\Model\Availability $availability,
        private \Psr\Log\LoggerInterface $logger) {}
    public function execute()
    {
        $result=$this->jsonFactory->create()->setHeader('Cache-Control','no-store',true);
        try {
            $p=$this->request;
            $sku=(string)$p->getParam('sku','*');
            if (strlen($sku)>64) throw new \Magento\Framework\Exception\LocalizedException(__('Invalid product.'));
            $location=$this->availability->location((string)$p->getParam('country',''),(int)$p->getParam('region',0),(int)$p->getParam('city',0),(int)$p->getParam('locality',0),(string)$p->getParam('city_name',''));
            return $result->setData($this->availability->check($location,$sku));
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            return $result->setHttpResponseCode(400)->setData(['error'=>(string)$e->getMessage()]);
        } catch (\Throwable $e) {
            $this->logger->error('Delivery availability check failed',['exception'=>$e]);
            return $result->setHttpResponseCode(503)->setData(['error'=>'Delivery availability temporarily unavailable.']);
        }
    }
}
