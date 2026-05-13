<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsCustomTheme\Block\Adminhtml;

/**
 *  Container for theme grid
 */
class Theme extends \Magento\Backend\Block\Widget\Container
{
    protected function _prepareLayout(){
        $this->addButton(
            'add',
            [
                'label' => __("Add Theme"),
                'onclick' => 'setLocation(\'' . $this->getCreateUrl() . '\')',
                'class' => 'add primary'
            ]
        );
        parent::_prepareLayout();
    }
    
    /**
     * Get add auction URL
     * 
     * @return string
     */
    public function getCreateUrl(){
        return $this->getUrl('vendors/theme/new');
    }

}