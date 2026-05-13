<?php
namespace Vnecoms\VendorsRMA\Block\Vendor\Escalate\Edit;

use Vnecoms\Vendors\Block\Vendors\Widget\Form as WidgetForm;

class Form extends \Vnecoms\Vendors\Block\Vendors\Widget\Form\Generic
{

    /**
     * @return WidgetForm
     */
    protected function _prepareForm()
    {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            ['data' => ['id' => 'edit_form', 'action' => $this->getData('action'), 'method' => 'post']]
        );
        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }
}