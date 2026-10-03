<?php
namespace MagentoEgypt\CityManager\Controller\Adminhtml\Location;
class Export extends \Magento\Backend\App\Action implements \Magento\Framework\App\Action\HttpGetActionInterface
{
    const ADMIN_RESOURCE='MagentoEgypt_CityManager::manage';
    public function execute() {
        try {
            $csv=$this->_objectManager->get(\MagentoEgypt\CityManager\Model\Directory::class)->exportCsv((string)$this->getRequest()->getParam('country','EG'));
            return $this->_objectManager->get(\Magento\Framework\App\Response\Http\FileFactory::class)->create('city-manager.csv',$csv,\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR,'text/csv; charset=UTF-8');
        } catch(\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $this->resultRedirectFactory->create()->setPath('*/*/index');
        }
    }
}
