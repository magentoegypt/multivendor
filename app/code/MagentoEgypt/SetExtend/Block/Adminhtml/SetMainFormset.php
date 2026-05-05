<?php
namespace MagentoEgypt\SetExtend\Block\Adminhtml;

use Magento\Backend\Block\Widget\Form;

/**
 * Form attribute set
 *
 * Class \MagentoEgypt\SetExtend\Block\Adminhtml\SetMainFormset
 */
class SetMainFormset extends \Magento\Backend\Block\Widget\Form\Generic
{
    /**
     * @var \Magento\Eav\Model\Entity\Attribute\SetFactory
     */
    protected $_setFactory;

    /**
     * @var \Magento\Store\Model\StoreFactory
     */
    protected $_storeFactory;

    /**
     * @var \MagentoEgypt\SetExtend\Helper\Data
     */
    protected $helper;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Store\Model\StoreFactory $storeFactory
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Eav\Model\Entity\Attribute\SetFactory $setFactory
     * @param \MagentoEgypt\SetExtend\Helper\Data $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Store\Model\StoreFactory $storeFactory,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Eav\Model\Entity\Attribute\SetFactory $setFactory,
        \MagentoEgypt\SetExtend\Helper\Data $helper,
        array $data = []
    ) {
        $this->_setFactory = $setFactory;
        $this->_storeFactory = $storeFactory;
        $this->helper = $helper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepares attribute set form
     *
     * @return void
     */
    protected function _prepareForm()
    {
        $data = $this->_setFactory->create()->load($this->getRequest()->getParam('id'));

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $fieldset = $form->addFieldset('set_name', ['legend' => $this->getAttributeSetLabel()]);
        $fieldset->addField(
            'attribute_set_name',
            'text',
            [
                'label' => __('Name'),
                'note' => __('For internal use'),
                'name' => 'attribute_set_name',
                'required' => true,
                'class' => 'required-entry validate-no-html-tags',
                'value' => $data->getAttributeSetName()
            ]
        );
        $id = $this->getRequest()->getParam('id', false);

        if (!$id) {
            $fieldset->addField('gotoEdit', 'hidden', ['name' => 'gotoEdit', 'value' => '1']);

            $sets = $this->_setFactory->create()->getResourceCollection()->setEntityTypeFilter(
                $this->_coreRegistry->registry('entityType')
            )->load()->toOptionArray();

            $fieldset->addField(
                'skeleton_set',
                'select',
                [
                    'label' => __('Based On'),
                    'name' => 'skeleton_set',
                    'required' => true,
                    'class' => 'required-entry',
                    'values' => $sets
                ]
            );
        } else {
            $fieldset = $form->addFieldset('set_store_titles', ['legend' => __('Manage Set Titles')]);
            $titles = ($id > 0) ? $this->helper->getSetTitle($id) : [];
            foreach ($this->getStores() as $store)
            {
                $value = $titles[$store->getId()] ?? "";
                $fieldset->addField(
                    'attribute_set_title_' . $store->getId(),
                    'text',
                    [
                        'label' => __($store->getName()),
                        'name' => 'attribute_set_title_' . $store->getId(),
                        'required' => false,
                        'class' => 'validate-no-html-tags attribute-set-title',
                        'value' => $value
                    ]
                );
            }
        }

        $form->setMethod('post');
        $form->setUseContainer(true);
        $form->setId('set-prop-form');
        $form->setAction($this->getUrl('catalog/*/save'));
        $form->setOnsubmit('return false;');
        $this->setForm($form);
    }

    /**
     * Get Attribute Set Label
     *
     * @return \Magento\Framework\Phrase
     */
    private function getAttributeSetLabel()
    {
        if ($this->getRequest()->getParam('id', false)) {
            return __('Edit Attribute Set Name');
        }

        return __('Attribute Set Information');
    }

    /**
     * @return mixed
     */
    public function getStores()
    {
        $stores = $this->getData('stores');
        if ($stores === null) {
            $stores = $this->_storeFactory->create()->getResourceCollection()->setOrder('store_id','ASC')->setLoadDefault(false)->load();
            $this->setData('stores', $stores);
        }
        return $stores;
    }    
}
