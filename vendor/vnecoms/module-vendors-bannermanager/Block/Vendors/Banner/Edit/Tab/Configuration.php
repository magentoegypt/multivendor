<?php

namespace Vnecoms\BannerManager\Block\Vendors\Banner\Edit\Tab;

use Magento\Framework\Module\Manager;
/**
 * Class Configuration in edit form
 * 
 * @category Vnecoms
 * @package  Vnecoms_BannerManager
 * @module   BannerManager
 * @author   Vnecoms Developer Team
 */
class Configuration extends \Vnecoms\Vendors\Block\Vendors\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /** @var \Vnecoms\BannerManager\Model\EasingFactory  */
    protected $_easingFactory;

    /** @var  \Magento\Framework\App\ResourceConnection */
    protected $_resourceConnection;

    /** @var \Magento\Store\Model\StoreManagerInterface  */
    protected $_storeManager;

    /** @var \Magento\Store\Model\System\Store  */
    protected $_systemStore;

    /** @var \Vnecoms\BannerManager\Helper\Data  */
    protected $_bannerHelper;

    /** @var \Magento\Framework\View\Result\LayoutFactory  */
    protected $_layout;

    /** @var  \Vnecoms\BannerManager\Model\SliderFactory */
    protected $sliderFactory;

    protected $sliderModeConfig;

    protected $sliderDirectionConfig;

    /** @var Manager  */
    protected $_moduleManager;

    protected $_sourceTemplate;


    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Vnecoms\BannerManager\Model\EasingFactory $easingFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Store\Model\System\Store $systemStore,
        \Magento\Framework\View\Result\LayoutFactory $layoutFactory,
        \Vnecoms\BannerManager\Helper\Data $bannerHelper,
        \Vnecoms\BannerManager\Model\SliderFactory $sliderFactory,
        \Vnecoms\BannerManager\Model\Config\Slider\Mode $sliderModeConfig,
        \Vnecoms\BannerManager\Model\Config\Slider\Direction $sliderDirectionConfig,
        \Vnecoms\BannerManager\Model\Source\Template $sourceTemplate,
        Manager $moduleManager,
        array $data = []
    ) {

        $this->_easingFactory = $easingFactory;
        $this->_resourceConnection = $resourceConnection;
        $this->_storeManager = $context->getStoreManager();
        $this->_systemStore = $systemStore;
        $this->_layout = $layoutFactory;
        $this->_bannerHelper = $bannerHelper;
        $this->sliderFactory = $sliderFactory;
        $this->sliderModeConfig = $sliderModeConfig;
        $this->sliderDirectionConfig = $sliderDirectionConfig;
        $this->_moduleManager = $moduleManager;
        $this->_sourceTemplate = $sourceTemplate;
        parent::__construct($context, $registry, $formFactory, $data);

    }

    /**
     * Prepare global layout
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareLayout()
    {
        $this->pageConfig->getTitle()->set($this->getPageTitle());
        return parent::_prepareLayout();
    }

    /**
     * Call before Html
     *
     * @return $this
     */
    protected function _beforeToHtml()
    {
        return parent::_beforeToHtml();
    }

    /**
     * Form prepare
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareForm()
    {
        $model = $this->getBanner();

        $form = $this->_formFactory->create();

        $form->setHtmlIdPrefix('configuration_');

        $fieldset = $form->addFieldset(
            'base_fieldset',
            [
                'legend' => __('Configuration'),
                'class' => 'fieldset-wide'
            ]
        );

        $fieldset->addField(
            'banner_template',
            'select',
            [
                'label' => __('Select Slider Template'),
                'name' => 'banner_template',
                'required' => true,
                'values' => $this->_bannerHelper->getSliderTemplate()
            ]
        );

        $fieldset->addField(
            'effect',
            'select',
            [
                'label'     => __('Animation Effect'),
                'name'      => 'effect',
                'required'  => true,
                'disabled'  => '',
                'values'    => $this->_bannerHelper->getEffect(),
            ]
        );


        $fieldset->addField(
            'speed',
            'text',
            [
                'label'     => __('Banner Speed'),
                'class'     => 'validate-number validate-greater-than-zero',
                'name'      => 'speed',
                'disabled'  => '',
                'note' => 'Duration of transition between slides (ms): Default 300ms',
                'values'    => [
                ],
            ]
        );

        $fieldset->addField(
            'delay',
            'text',
            [
                'label'     => __('Autoplay'),
                'class'     => 'validate-number validate-greater-than-zero',
                'required'  => false,
                'disabled'  => '',
                'name'      => 'delay',
                'note' => __('Autoplay after  (ms), Empty value will disable autoplay')
            ]
        );

        $fieldset->addField(
            'hide_pagination',
            'select',
            [
                'label' => __('Hide Pagination'),
                'name' => 'hide_pagination',
                'required' => false,
                'values'    => [
                    0   => __('No'),
                    1   => __('Yes')
                ],
            ]
        );

        $fieldset->addField(
            'type_pagination',
            'select',
            [
                'label' => __('Type of pagination'),
                'name' => 'type_pagination',
                'required' => false,
                'values'    => [
                    'bullets'   => __('Bullets'),
                    'fraction'   => __('Fraction'),
                    'progress'   => __('Progress'),
                    'custom'   => __('Custom'),
                ],
            ]
        );

        $fieldset->addField(
            'sort_order',
            'text',
            [
                'label'     => __('Sort Order'),
                'name'      => 'sort_order',
                'disabled'  => '',
                'values'    => [
                ],
            ]
        );

        // Choose Banner Position in storefront
        /*if (!$this->isModuleVendorsCmsEnabled()) {
            $fieldset->addField(
                'banner_position',
                'select',
                [
                    'name' => 'banner_position',
                    'label' => __('Banner Position'),
                    'title' => __('Banner Position'),
                    'values' => $this->_bannerHelper->getBlockIdsToOptionsArray()
                ]
            );
        }*/

        if (!$model->getId() && $model->getData('speed') == null) {
            $model->setData('speed', 300);
        }

        if (!$model->getId()) {
            $model->setData('show_item_description', 0);
        }

        // Set data to field
        if ($model->getId()) $form->setValues($model->getData());
        $this->setForm($form);
        return parent::_prepareForm();
    }

    protected function isModuleVendorsCmsEnabled()
    {
        return $this->_moduleManager->isEnabled('Vnecoms_VendorsCms');
    }

    /**
     * Processing block html after rendering
     *
     * @param string $html
     * @return string
     */
    protected function _afterToHtml($html)
    {
        $form = $this->getForm();
        $htmlIdPrefix = $form->getHtmlIdPrefix();

        /**
         * Form template has possibility to render child block 'form_after', but we can't use it because parent
         * form creates appropriate child block and uses this alias. In this case we can't use the same alias
         * without core logic changes, that's why the code below was moved inside method '_afterToHtml'.
         */
        /** @var $formAfterBlock \Magento\Backend\Block\Widget\Form\Element\Dependence */
        $formAfterBlock = $this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Form\Element\Dependence',
            'vendors.block.widget.form.pagination.dependence'
        );
        $formAfterBlock->addFieldMap(
            $htmlIdPrefix . 'hide_pagination',
            'hide_pagination'
        )->addFieldMap(
            $htmlIdPrefix . 'type_pagination',
            'type_pagination'
        )->addFieldDependence(
            'type_pagination',
            'hide_pagination',
            '0'
        );


       $html = $html . $formAfterBlock->toHtml();

       return $html;
    }


    /**
     * Get banner in registry
     *
     * @return mixed
     */
    public function getBanner()
    {
        return $this->_coreRegistry->registry('banner');
    }


    /**
     * Get page Title
     *
     * @return \Magento\Framework\Phrase
     */
    public function getPageTitle()
    {
        return $this->getBanner()->getId() ? __("Edit Banner '%1'", $this->escapeHtml($this->getBanner()->getTitle())) : __('New Bannner');
    }

    /**
     * Get Tab Label
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Configuration');
    }

    /**
     * Get Tab Title
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return $this->getTabLabel();
    }

    /**
     * Check Show Tab
     *
     * @return bool
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Check Hidden
     *
     * @return bool
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * @param $resourceId
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }
}
