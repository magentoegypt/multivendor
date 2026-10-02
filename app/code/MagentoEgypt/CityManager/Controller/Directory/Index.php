<?php
namespace MagentoEgypt\CityManager\Controller\Directory;
class Index implements \Magento\Framework\App\Action\HttpGetActionInterface
{
    public function __construct(private \Magento\Framework\App\RequestInterface $request,private \Magento\Framework\Controller\Result\JsonFactory $json,private \MagentoEgypt\CityManager\Model\Directory $directory) {}
    public function execute() {
        $out=$this->json->create(); $out->setHeader('Cache-Control','no-store, max-age=0',true);
        try { return $out->setData(['items'=>$this->directory->options(strtoupper((string)$this->request->getParam('country','EG')),(string)$this->request->getParam('level','region'),(int)$this->request->getParam('parent',0),(int)$this->request->getParam('region',0),(string)$this->request->getParam('q',''))]); }
        catch(\Magento\Framework\Exception\LocalizedException $e) { return $out->setHttpResponseCode(400)->setData(['error'=>$e->getMessage()]); }
    }
}
