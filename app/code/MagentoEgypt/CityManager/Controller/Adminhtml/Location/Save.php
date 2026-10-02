<?php
namespace MagentoEgypt\CityManager\Controller\Adminhtml\Location;
class Save extends \Magento\Backend\App\Action implements \Magento\Framework\App\Action\HttpPostActionInterface
{
    const ADMIN_RESOURCE='MagentoEgypt_CityManager::manage';
    public function execute() {
        try { $id=$this->_objectManager->get(\MagentoEgypt\CityManager\Model\Directory::class)->save($this->getRequest()->getPostValue(),(int)$this->_auth->getUser()->getId()); $this->messageManager->addSuccessMessage(__('Location saved.')); }
        catch(\Magento\Framework\Exception\LocalizedException $e) {$this->messageManager->addErrorMessage($e->getMessage());}
        catch(\Throwable $e) {$this->messageManager->addErrorMessage(__('Unable to save. Check for a duplicate code.'));}
        return $this->resultRedirectFactory->create()->setPath('*/*/index',['id'=>$id??null,'country'=>$this->getRequest()->getParam('country_id'),'level'=>$this->getRequest()->getParam('level')]);
    }
}
