<?php 
namespace MagentoEgypt\SetExtend\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

class SaveTitle implements ObserverInterface
{
    protected $helper;
    protected $request;

    public function __construct(
        \MagentoEgypt\SetExtend\Helper\Data $helper,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $this->helper = $helper;
        $this->request = $request;
    }

    public function execute(Observer $observer)
    {
        $setObject = $observer->getObject();
        if($setObject->getId() > 0) {
            $data = json_decode($this->request->getPost('data'), true);
            $this->helper->saveSetTitles( $setObject->getId(), $data['attribute_set_title'] ?? []);
        }
    }
}