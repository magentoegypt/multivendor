<?php
namespace Vnecoms\VendorsCustomTheme\Block\Vendors\Theme\Edit;

use Magento\Backend\Block\Widget\Form as WidgetForm;

class Form extends \Vnecoms\Vendors\Block\Vendors\Widget\Form\Generic
{
    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('user_form');
        $this->setTitle(__('Theme Configuration'));
    }

    
    /**
     * @return WidgetForm
     */
    protected function _prepareForm()
    {
        /** @var \Magento\Framework\Data\Form $form */
        $saveUrl = $this->_getSaveUrl();
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id' => 'edit_form',
                    'action' => $saveUrl,
                    'method' => 'post',
                ],
            ]
        );
        $form->setUseContainer(true);
        $this->setForm($form);
        
        return parent::_prepareForm();
    }
    
    /**
     * Get Save Url
     * @return string
     */
    protected function _getSaveUrl()
    {
        return $this->getUrl('theme/index/save', ['id' => $this->getRequest()->getParam('id')]);
    }
}
