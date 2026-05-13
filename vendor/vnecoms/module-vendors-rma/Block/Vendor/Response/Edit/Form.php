<?php
namespace Vnecoms\VendorsRMA\Block\Vendor\Response\Edit;

use Vnecoms\Vendors\Block\Vendors\Widget\Form as WidgetForm;

class Form extends \Vnecoms\Vendors\Block\Vendors\Widget\Form\Generic
{


    /**
     * Main constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Vnecoms\VendorsRMA\Model\Source\Status $status,
        \Vnecoms\VendorsRMA\Model\Source\Wysiwyg\Config $wysiwygConfig,

        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
        $this->_status = $status;
        $this->_wysiwygConfig = $wysiwygConfig;
    }

    /**
     * @return Form
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('vendorsrma_response_form');
        $this->setTitle(__('Response Information'));
    }


    /**
     * Prepare form
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        /** @var \Vnecoms\RMA\Model\Reponse $model */
        $model = $this->_coreRegistry->registry('current_reponse');

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            ['data' => ['id' => 'edit_form', 'action' => $this->getData('action'), 'method' => 'post']]
        );

        $form->setHtmlIdPrefix('response_');

        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('General Information'), 'class' => 'fieldset-wide']
        );

        if ($model->getId()) {
            $fieldset->addField('reponse_id', 'hidden', ['name' => 'reponse_id']);
        }

        $fieldset->addField(
            'status',
            'select',
            [
                'name' => 'status',
                'label' => __('Status'),
                'title' => __('Status'),
                'required' => true,
                'options' => $this->_status->toOptionArray()
            ]
        );

        $fieldset->addField(
            'title',
            'text',
            ['name' => 'title',
                'label' => __('Title'),
                'title' => __('Title'),
                'width'   => '400px',
                'required' => true
            ]
        );
        $fieldset->addField(
            'content',
            'editor',
            [
                'name' => 'content',
                'label' => __('Content'),
                'title' => __('Content'),
                'required' => true,
                'rows' => '10',
                'cols' => '70',
                'state' => 'html',
            ]
        );

        $form->setValues($model->getData());
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}