<?php
namespace Vnecoms\VendorsRMA\Block\Vendor\Escalate\Edit\Tab;

use Vnecoms\Vendors\Block\Vendors\Widget\Form;
use Vnecoms\Vendors\Block\Vendors\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;

class Main extends Generic implements TabInterface
{

    /**
     * @var \Vnecoms\VendorsRMA\Model\Source\Wysiwyg\Config
     */
    protected $_wysiwygConfig;

    /**
     * Main constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Vnecoms\VendorsRMA\Model\Source\Wysiwyg\Config $wysiwygConfig
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Vnecoms\VendorsRMA\Model\Source\Wysiwyg\Config $wysiwygConfig,
        array $data = []
    ) {
        $this->_wysiwygConfig = $wysiwygConfig;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare content for tab
     *
     * @return \Magento\Framework\Phrase
     * @codeCoverageIgnore
     */
    public function getTabLabel()
    {
        return __('Escalate Information');
    }

    /**
     * Prepare title for tab
     *
     * @return \Magento\Framework\Phrase
     * @codeCoverageIgnore
     */
    public function getTabTitle()
    {
        return __('Escalate Information');
    }

    /**
     * Returns status flag about this tab can be showed or not
     *
     * @return bool
     * @codeCoverageIgnore
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Returns status flag about this tab hidden or not
     *
     * @return bool
     * @codeCoverageIgnore
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * @return Form
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('escalate_form');
        $this->setTitle(__('Escalate Information'));
    }

    /**
     * Prepare form
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        /** @var \Vnecoms\VendorsRMA\Model\Request $model */
        $model = $this->_coreRegistry->registry('current_request');

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();

        $form->setHtmlIdPrefix('escalate_form_');

        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('Escalate Information')]
        );

        if ($model->getId()) {
            $fieldset->addField('request_id', 'hidden', ['name' => 'request[request_id]',"value"=> $model->getId()]);
        }

        $wysiwygConfig = $this->_wysiwygConfig->getConfig();
        $fieldset->addField(
            'answer',
            'editor',
            [
                'name' => 'request[message]',
                'label' => __('Message'),
                'title' => __('Message'),
                'required' => true,
                'style' => 'width:100%; height:250px;',
                'state' => 'html',
                'config' => $wysiwygConfig,
            ]
        );

        $contentField = $fieldset->addField(
            'attachment',
            'text',
            [
                'name' => 'attachment',
                'style' => 'height:36em;',
            ]
        );
        // Setting custom renderer for content field to remove label column
        $renderer = $this->getLayout()->createBlock(
            'Vnecoms\VendorsRMA\Block\Vendor\Escalate\Renderer\Attachment'
        )->setTemplate(
            'Vnecoms_VendorsRMA::request/escalate/attachment.phtml'
        );
        $contentField->setRenderer($renderer);

        $this->setForm($form);
        return parent::_prepareForm();
    }
}
