<?php
namespace MagentoEgypt\CityManager\Controller\Adminhtml\Location;
class Index extends \Magento\Backend\App\Action implements \Magento\Framework\App\Action\HttpGetActionInterface
{
    const ADMIN_RESOURCE='MagentoEgypt_CityManager::manage';
    public function execute() { $page=$this->_objectManager->get(\Magento\Framework\View\Result\PageFactory::class)->create();$page->getConfig()->getTitle()->prepend(__('City Manager'));return $page; }
}
