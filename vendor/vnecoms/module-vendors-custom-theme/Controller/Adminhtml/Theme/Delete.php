<?php
namespace Vnecoms\VendorsCustomTheme\Controller\Adminhtml\Theme;

use Vnecoms\Vendors\Controller\Adminhtml\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\Filter\Date;
use Magento\Framework\Exception\LocalizedException;

class Delete extends Action
{
    /**
     * Is access to section allowed
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return parent::_isAllowed() && $this->_authorization->isAllowed('Vnecoms_VendorsCustomTheme::theme');
    }
    
    /**
     * @return void
     */
    public function execute()
    {
        try {
            /** @var \Vnecoms\VendorsCustomTheme\Model\Theme $model */
            $model = $this->_objectManager->create('Vnecoms\VendorsCustomTheme\Model\Theme');

            $id = $this->getRequest()->getParam('id');
            $model->setId($id)->delete();
            
            $this->messageManager->addSuccess(__('The theme has been deleted.'));
            
            return $this->_redirect('vendors/theme');
        } catch (\Exception $e) {
            $this->messageManager->addError(
                $e->getMessage()
            );
            $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
            return $this->_redirect('vendors/theme/edit', ['id' => $this->getRequest()->getParam('id')]);
        }
    }
}
