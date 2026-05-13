<?php
/**
 * Created by PhpStorm.
 * User: nvhai
 * Date: 12/21/2016
 * Time: 05:50 PM
 */
namespace Vnecoms\RMA\Block\Adminhtml\Status\Edit\Tab;

class Labels extends \Magento\Backend\Block\Widget\Form\Generic implements
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
    protected $_nameInLayout = 'store_view_labels';

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

        $labels = $status->getStoreLabels();

        if (!$this->_storeManager->isSingleStoreMode()) {
            $fieldset = $this->_createFieldset($form, $labels);
        }

        $this->setForm($form);
        return parent::_prepareForm();
    }

    /**
     * Create view fieldset
     *
     * @param \Magento\Framework\Data\Form $formElement
     * @param array $labelStore
     * @return \Magento\Framework\Data\Form\Element\Fieldset
     */
    protected function _createFieldset($formElement, $labelStore)
    {
        $fieldset = $formElement->addFieldset(
            'labels_fieldset',
            ['legend' => __('Store View Specific Labels'), 'class' => 'store-scope']
        );
        $renderer = $this->getLayout()->createBlock('Magento\Backend\Block\Store\Switcher\Form\Renderer\Fieldset');
        $fieldset->setRenderer($renderer);

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
                        "s_{$store->getId()}",
                        'text',
                        [
                            'name' => 'store_labels[' . $store->getId() . ']',
                            'title' => $store->getName(),
                            'label' => $store->getName(),
                            'required' => false,
                            'value' => isset($labelStore[$store->getId()]) ? $labelStore[$store->getId()] : '',
                            'fieldset_html_class' => 'store',
                            'data-form-part' => 'rma_status_form'
                        ]
                    );
                }
            }
        }
        return $fieldset;
    }
}
