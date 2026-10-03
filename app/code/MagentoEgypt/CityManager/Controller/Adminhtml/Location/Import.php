<?php
namespace MagentoEgypt\CityManager\Controller\Adminhtml\Location;
class Import extends \Magento\Backend\App\Action implements \Magento\Framework\App\Action\HttpPostActionInterface
{
    const ADMIN_RESOURCE='MagentoEgypt_CityManager::manage';
    public function execute() {
        try {
            $file=$this->getRequest()->getFiles('locations_csv');
            if(!$file || ($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name']) || $file['size']>5*1024*1024) throw new \Magento\Framework\Exception\LocalizedException(__('Upload a CSV file of at most 5 MB.'));
            $result=$this->_objectManager->get(\MagentoEgypt\CityManager\Model\Directory::class)->importCsv(file_get_contents($file['tmp_name']),(int)$this->_auth->getUser()->getId());
            $this->messageManager->addSuccessMessage(__('Import complete: %1 created, %2 updated, %3 unchanged.',$result['created'],$result['updated'],$result['unchanged']));
        } catch(\Magento\Framework\Exception\LocalizedException $e) {$this->messageManager->addErrorMessage($e->getMessage());}
        return $this->resultRedirectFactory->create()->setPath('*/*/index');
    }
}
