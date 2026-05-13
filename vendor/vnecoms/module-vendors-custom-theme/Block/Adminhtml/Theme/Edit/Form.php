<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCustomTheme\Block\Adminhtml\Theme\Edit;

use Magento\Backend\Block\Widget\Form as WidgetForm;

class Form extends \Magento\Backend\Block\Widget\Form\Generic
{
    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('theme_form');
        $this->setTitle(__('Theme Information'));
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
                    'enctype' => 'multipart/form-data',
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
    protected function _getSaveUrl(){
        return $this->getUrl('vendors/theme/save',[
            'id' => $this->getRequest()->getParam('id'),
            'section' => $this->getRequest()->getParam('section'),
        ]);
    }
}
