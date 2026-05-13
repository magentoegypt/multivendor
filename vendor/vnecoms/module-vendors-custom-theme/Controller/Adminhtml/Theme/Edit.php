<?php
namespace Vnecoms\VendorsCustomTheme\Controller\Adminhtml\Theme;

use Vnecoms\VendorsCustomTheme\Controller\Adminhtml\Action;

class Edit extends Action
{
    /**
     * @return void
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $model = $this->_objectManager->create('Vnecoms\VendorsCustomTheme\Model\Theme');

        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addError(__('This theme no longer exists.'));
                $this->_redirect('vendors/theme/');
                return;
            }
        }
        
        $this->_coreRegistry->register('theme', $model);
        $this->_coreRegistry->register('current_theme', $model);

        $this->_view->loadLayout();

        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Manage Vendor Theme'));
        $this->_view->getPage()->getConfig()->getTitle()->prepend(
            $model->getThemeId() ? $model->getTitle() : __('New Theme')
        );
        

        $breadcrumb = $id ? __('Edit Theme') : __('New Theme');
        $this->_addBreadcrumb($breadcrumb, $breadcrumb);
        $this->_view->renderLayout();
    }
}
