<?php
namespace Vnecoms\VendorsRMA\Block\Adminhtml\Request\Mark;

class Form extends \Magento\Backend\Block\Widget\Form\Generic
{

    /**
     * @return WidgetForm
     */
    protected function _prepareForm()
    {
        /** @var \Vnecoms\VendorsRMA\Model\Request $model */
        $model = $this->_coreRegistry->registry('current_request');

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            ['data' => ['id' => 'edit_form', 'action' => $this->getData('action'), 'method' => 'post']]
        );
        $form->setUseContainer(true);
        $this->setForm($form);
        $fieldsetCore = $form->addFieldset('core_fieldset', ['legend' => __('Mark as Resolve')]);

        if ($model->getId()) {
            $fieldsetCore->addField('request_id', 'hidden', ['name' => 'request_id',"value"=> $model->getId()]);
        }

        $fieldsetCore->addField(
            'resolve_type',
            'select',
            [
                'name' => 'resolve_type',
                'values' => [
                    "" => __("-- Select Solution --"),
                    "accept" => __("Accept Customer's RMA Request"),
                    "deny" => __("Deny Customer's RMA Request"),
                    "resolve" => __("Resolve Customer's RMA Request")
                ],
                'label' => __('Solution'),
                'class' => 'required-entry',
                'required' => true
            ]
        );

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Customer')]);
        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $templateCustomer = $object_manager->get('\Vnecoms\VendorsRMA\Model\Request\Escalate\Template')
            ->getOptionCustomerTemplate();
        $templateCustomer[""] = __("-- Select Template --");
        ksort($templateCustomer);

        $fieldset->addField(
            'template_customer',
            'select',
            [
                'name' => 'template_customer',
                'values' => $templateCustomer,
                'label' => __('Template Customer'),
                'class' => 'required-entry',
                'required' => true,
                'onchange' => "changeTemplate('customer')"
            ]
        );

        $contentField = $fieldset->addField(
            'preview_customer',
            'text',
            [
                'name' => 'preview_customer',
            ]
        );
        // Setting custom renderer for content field to remove label column
        $renderer = $this->getLayout()->createBlock(
            'Vnecoms\VendorsRMA\Block\Adminhtml\Request\Mark\Renderer\Preview'
        )->setTemplate(
            'Vnecoms_VendorsRMA::request/escalate/preview.phtml'
        )->setType("customer");
        $contentField->setRenderer($renderer);


        $fieldsetVendor = $form->addFieldset('base_fieldset_vendor', ['legend' => __('Vendor')]);

        $templateVendor = $object_manager->get('\Vnecoms\VendorsRMA\Model\Request\Escalate\Template')
            ->getOptionVendorTemplate();
        $templateVendor[""] = __("-- Select Template --");
        ksort($templateVendor);


        $fieldsetVendor->addField(
            'template_vendor',
            'select',
            [
                'name' => 'template_vendor',
                'values' => $templateVendor,
                'label' => __('Template Vendor'),
                'class' => 'required-entry',
                'required' => true,
                'onchange' => "changeTemplate('vendor')"
        ]);

        $contentField = $fieldsetVendor->addField(
            'preview_vendor',
            'text',
            [
                'name' => 'preview_vendor',
            ]
        );
        // Setting custom renderer for content field to remove label column
        $renderer = $this->getLayout()->createBlock(
            'Vnecoms\VendorsRMA\Block\Adminhtml\Request\Mark\Renderer\Preview'
        )->setTemplate(
            'Vnecoms_VendorsRMA::request/escalate/preview.phtml'
        )->setType("vendor");
        $contentField->setRenderer($renderer);

        return parent::_prepareForm();
    }
}