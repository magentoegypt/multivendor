<?php
namespace Vnecoms\VendorsSms\Controller\Adminhtml\Sms\Transaction;

use Vnecoms\Vendors\Controller\Adminhtml\Action;

class Grid extends Action
{

    /**
     * @return void
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $model = $this->_objectManager->create('Vnecoms\Vendors\Model\Vendor');
        $model->load($id);
        $this->_coreRegistry->register('current_vendor', $model);
        
        $grid = $this->_view->getLayout()->createBlock('Vnecoms\VendorsSms\Block\Adminhtml\Vendor\Edit\Tab\Sms\Grid');
        return $this->getResponse()->setBody($grid->toHtml());
    }
}
