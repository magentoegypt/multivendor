<?php
/**
 * Created by PhpStorm.
 * User: nvhai
 * Date: 12/23/2016
 * Time: 11:13 AM
 */
namespace Vnecoms\RMA\Controller\Adminhtml\Response;

class Edit extends Reponse
{
    /**
     * @return void
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('reponse_id');
        $model = $this->_objectManager->create('Vnecoms\RMA\Model\Reponse');

        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addError(__('This Respons no longer exists.'));
                $this->_redirect('*/*');
                return;
            }
        }

        $this->_coreRegistry->register('current_reponse', $model);

        $this->_initAction();
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Manage Response'));
        $this->_view->getPage()->getConfig()->getTitle()->prepend(
            $model->getId() ? $model->getTitle() : __('New Response')
        );

        $breadcrumb = $id ? __('Edit Reponse') : __('New Response');
        $this->_addBreadcrumb($breadcrumb, $breadcrumb);
        $this->_view->renderLayout();
    }
}
