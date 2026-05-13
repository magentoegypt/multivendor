<?php
/**
 * Created by PhpStorm.
 * User: nvhai
 * Date: 12/21/2016
 * Time: 05:50 PM
 */
namespace Vnecoms\RMA\Block\Adminhtml\Status\Edit\Tab;

class Template extends \Magento\Backend\Block\Widget\Form\Generic implements
    \Magento\Ui\Component\Layout\Tabs\TabInterface
{
    /**
     * @var \Vnecoms\RMA\Model\Status
     */
    private $statusFactory;

    /**
     * Labels constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Vnecoms\RMA\Model\Status $statusFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Vnecoms\RMA\Model\StatusFactory $statusFactory,
        array $data = []
    ) {
        $this->statusFactory = $statusFactory;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * @var string
     */
    protected $_nameInLayout = 'store_view_email_template';

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function getTabClass()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function getTabUrl()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function isAjaxLoaded()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function getTabLabel()
    {
        return __('Labels');
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function getTabTitle()
    {
        return __('Labels');
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Prepare form before rendering HTML
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        $status = $this->_coreRegistry->registry('current_status');

        if (!$status) {
            $id = $this->getRequest()->getParam('status_id');
            $status = $this->statusFactory->create();
            $status->load($id);
        }

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('rma_');

        $templates = $status->getEmailTemplates();

        if (!$this->_storeManager->isSingleStoreMode()) {
            $fieldset = $this->_createStoreSpecificFieldset($form, $templates);
            /*if ($status->isReadonly()) {
                foreach ($fieldset->getElements() as $element) {
                    $element->setReadonly(true, true);
                }
            }*/
        }

        $this->setForm($form);
        return parent::_prepareForm();
    }

    /**
     * Create store specific fieldset
     *
     * @param \Magento\Framework\Data\Form $form
     * @param array $labels
     * @return \Magento\Framework\Data\Form\Element\Fieldset
     */
    protected function _createStoreSpecificFieldset($form, $templates)
    {
        $fieldset = $form->addFieldset(
            'store_template_fieldset',
            ['legend' => __('Store View Template Notify'), 'class' => 'store-scope']
        );
        $renderer = $this->getLayout()->createBlock('Magento\Backend\Block\Store\Switcher\Form\Renderer\Fieldset');
        $fieldset->setRenderer($renderer);
        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $statusAdmin = $object_manager->get('\Vnecoms\RMA\Ui\Component\Listing\Columns\Status\Template\StatusAdmin')
            ->toOptionArray();
        $statusCustomer = $object_manager->get('\Vnecoms\RMA\Ui\Component\Listing\Columns\Status\Template\StatusCustomer')
            ->toOptionArray();

        foreach ($this->_storeManager->getWebsites() as $website) {
            $fieldset->addField(
                "w_{$website->getId()}_label",
                'note',
                ['label' => $website->getName(), 'fieldset_html_class' => 'website']
            );
            foreach ($website->getGroups() as $group) {
                $stores = $group->getStores();
                if (count($stores) == 0) {
                    continue;
                }
                $fieldset->addField(
                    "sg_{$group->getId()}_label",
                    'note',
                    ['label' => $group->getName(), 'fieldset_html_class' => 'store-group']
                );
                foreach ($stores as $store) {
                    $fieldset->addField(
                        "s_admin_{$store->getId()}",
                        'select',
                        [
                            'name' => 'store_templates[' . $store->getId() . '][template_admin_notify]',
                            'title' => __("Send Admin Template"),
                            'label' => __("Send Admin Template"),
                            'required' => false,
                            'value' => isset($templates[$store->getId()]['template_admin_notify']) ?
                                $templates[$store->getId()]['template_admin_notify'] : '',
                            'fieldset_html_class' => 'store',
                            'data-form-part' => 'rma_status_form',
                            'values' => $statusAdmin
                        ]
                    );


                    $fieldset->addField(
                        "s_customer_{$store->getId()}",
                        'select',
                        [
                            'name' => 'store_templates[' . $store->getId() . '][template_customer_notify]',
                            'title' => __("Send Customer Template"),
                            'label' => __("Send Customer Template"),
                            'required' => false,
                            'value' => isset($templates[$store->getId()]['template_customer_notify']) ?
                                $templates[$store->getId()]['template_customer_notify'] : '',
                            'fieldset_html_class' => 'store',
                            'data-form-part' => 'rma_status_form',
                            'values' => $statusCustomer
                        ]
                    );
                }
            }
        }
        return $fieldset;
    }
}
