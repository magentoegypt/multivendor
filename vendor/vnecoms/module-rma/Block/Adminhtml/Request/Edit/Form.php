<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * description
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
namespace Vnecoms\RMA\Block\Adminhtml\Request\Edit;

use Magento\Backend\Block\Widget\Form as WidgetForm;

class Form extends \Magento\Backend\Block\Widget\Form\Generic
{
    /**
     * @var string
     */
    protected $_template = 'Vnecoms_RMA::request/widget/form.phtml';
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
        $tabs = $this->getLayout()->createBlock('Vnecoms\RMA\Block\Adminhtml\Request\Edit\Tabs');
        $this->setChild('tabs', $tabs);
        return parent::_prepareLayout();
    }

    public function getTabsHtml()
    {
        return $this->getChildHtml('tabs');
    }
}
