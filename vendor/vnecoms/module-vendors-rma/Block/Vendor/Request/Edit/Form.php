<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */


namespace Vnecoms\VendorsRMA\Block\Vendor\Request\Edit;

use \Vnecoms\Vendors\Block\Vendors\Widget\Form as WidgetForm;

class Form extends \Vnecoms\Vendors\Block\Vendors\Widget\Form\Generic
{
    /**
     * @var string
     */
    protected $_template = 'Vnecoms_VendorsRMA::request/widget/form.phtml';
    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('rma_request_view');
        $this->setTitle(__('RMA Information'));
    }

    /**
     * @return WidgetForm
     */
    protected function _prepareForm()
    {
        
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id' => 'edit_form',
                    'action' => $this->getUrl('*/*/reply'),
                    'method' => 'post',
                ],
            ]
        );
        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }

    protected function _prepareLayout()
    {
        $tabs = $this->getLayout()->createBlock('Vnecoms\VendorsRMA\Block\Vendor\Request\Edit\Tabs');
        $this->setChild('tabs', $tabs);
        return parent::_prepareLayout();
    }

    public function getTabsHtml()
    {
        return $this->getChildHtml('tabs');
    }
}
