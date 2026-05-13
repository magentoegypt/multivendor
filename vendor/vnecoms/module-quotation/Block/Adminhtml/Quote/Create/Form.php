<?php
/**
 * Copyright (c) 2017 Vnecoms Co ltd. All rights reserved.
 */
namespace Vnecoms\Quotation\Block\Adminhtml\Quote\Create;

use Magento\Framework\App\ObjectManager;

/**
 * Adminhtml quote create form block
 */
class Form extends \Magento\Backend\Block\Widget\Form\Generic
{
    /**
     * @var \Vnecoms\Quotation\Helper\Data
     */
    protected $helper;
    
    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Vnecoms\Quotation\Helper\Data $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Vnecoms\Quotation\Helper\Data $helper,
        array $data = []
    ) {
        $this->helper = $helper;
        parent::__construct($context, $registry, $formFactory, $data);
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
                    'action' => $this->getSubmitUrl(),
                    'method' => 'post',
                ],
            ]
        );
        $form->setUseContainer(true);

        $this->setForm($form);
        
        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Customer Information')]);
        $options = ObjectManager::getInstance()->get('Magento\Store\Ui\Component\Listing\Column\Store\Options')->toOptionArray();
        $fieldset->addField(
            'store_id',
            'select',
            ['name' => 'store_id', 'label' => __('Store'), 'title' => __('Store'), 'required' => true, 'values' => $options]
        );

        $chooserField = $fieldset->addField(
            'customer_id',
            'label',
            [
                'name' => 'customer_id',
                'label' => __('Customer Id #'),
            ]
        );
        /*Add chooser helper for the field*/
        $helperData  = [
            'hidden_enabled' => false,
            'button' => ['open' => __("Select Customer ...")]
        ];
        $helperBlock = $this->getLayout()->createBlock(
            'Vnecoms\Quotation\Block\Adminhtml\Quote\Create\Customer\Chooser',
            '',
            $helperData
        );
        
        $helperBlock->setConfig($helperData)
        ->setFieldsetId($fieldset->getId())
        ->prepareElementHtml($chooserField);
        
        $fieldset->addField(
            'customer_email',
            'text',
            [
                'name' => 'customer_email',
                'label' => __('Email'),
                'required' => true,
                'class' => 'widget-option',
            ]
        );
        
                
        $fieldset->addField(
            'customer_firstname',
            'text',
            ['name' => 'customer_firstname', 'label' => __('First Name'), 'title' => __('First Name'), 'required' => true]
        );

        $fieldset->addField(
            'customer_lastname',
            'text',
            ['name' => 'customer_lastname', 'label' => __('Last Name'), 'title' => __('Last Name'), 'required' => true]
        );
        if($config = $this->helper->getTelephoneConfig()){
            $fieldset->addField(
                'customer_phone',
                'text',
                ['name' => 'customer_phone', 'label' => __('Phone'), 'title' => __('Phone'), 'required' => $config == 2]
            );
        }
        if($config = $this->helper->getCompanyConfig()){
            $fieldset->addField(
                'customer_company',
                'text',
                ['name' => 'customer_company', 'label' => __('Company'), 'title' => __('Company'), 'required' => $config == 2]
            );
        }
        if($config = $this->helper->getTaxIdConfig()){
            $fieldset->addField(
                'customer_taxvat',
                'text',
                ['name' => 'customer_taxvat', 'label' => __('VAT/Tax ID'), 'title' => __('VAT/Tax ID'), 'required' => $config == 2]
            );
        }
        
        // add dependence javascript block
        $dependenceBlock = $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Form\Element\Dependence');
        $this->setChild('form_after', $dependenceBlock);
        $dependenceBlock->addFieldMap($chooserField->getId(), 'customer_email');
        
        return parent::_prepareForm();
    }
    
    public function getSubmitUrl()
    {
        return $this->getUrl('quotation/*/submit');
    }
}
