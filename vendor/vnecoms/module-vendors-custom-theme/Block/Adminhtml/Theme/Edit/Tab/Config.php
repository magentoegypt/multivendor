<?php
namespace Vnecoms\VendorsCustomTheme\Block\Adminhtml\Theme\Edit\Tab;

use Magento\Backend\Block\Widget\Form;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Vnecoms\Auction\Model\Auction;
use Magento\Framework\App\ObjectManager;

class Config extends Generic implements TabInterface
{
    const SCOPE_DEFAULT = 'default';
    
    const SCOPE_WEBSITES = 'websites';
    
    const SCOPE_STORES = 'stores';
    
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_date;

    /**
     * @var \Magento\Config\Model\Config\Structure\Element\Section
     */
    protected $_section;
    
    /**
     *Form fieldset factory
     *
     * @var \Vnecoms\VendorsCustomTheme\Block\Adminhtml\Theme\Edit\Form\Fieldset\Factory
     */
    protected $_fieldsetFactory;
    
    /**
     * Form field factory
     *
     * @var \Magento\Config\Block\System\Config\Form\Field\Factory
     */
    protected $_fieldFactory;
    
    /**
     * @var string
     */
    protected $_template = 'Vnecoms_VendorsCustomTheme::widget/form.phtml';
    
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    
    /**
     * @var string
     */
    protected $_sectionId;
    
		/**
		 * @var \Vnecoms\VendorsCustomTheme\Helper\Data
		 */
		protected $helper;
		
    /**
     * Prepare content for tab
     *
     * @return \Magento\Framework\Phrase
     * @codeCoverageIgnore
     */
    public function getTabLabel()
    {
        return $this->_section?$this->_section->getLabel():__("Theme Configuration");
    }
    
    /**
     * Prepare title for tab
     *
     * @return \Magento\Framework\Phrase
     * @codeCoverageIgnore
     */
    public function getTabTitle()
    {
        return $this->_section?$this->_section->getLabel():__("Theme Configuration");
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
    * 
    * @param \Magento\Backend\Block\Template\Context $context
    * @param \Magento\Framework\Registry $registry
    * @param \Magento\Framework\Data\FormFactory $formFactory
    * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
    * @param array $data
    */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Vnecoms\VendorsCustomTheme\Block\Adminhtml\Theme\Edit\Form\Fieldset\Factory $fieldsetFactory,
        \Magento\Config\Block\System\Config\Form\Field\Factory $fieldFactory,
				\Vnecoms\VendorsCustomTheme\Helper\Data $helper,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->_date = $date;
        $this->_fieldsetFactory = $fieldsetFactory;
        $this->_fieldFactory = $fieldFactory;
        $this->scopeConfig = $context->getScopeConfig();
				$this->helper = $helper;
        parent::__construct($context, $registry, $formFactory, $data);
    }


    /**
     * Set section
     * 
     * @param \Magento\Config\Model\Config\Structure\Element\Section $section
     * @return \Vnecoms\VendorsCustomTheme\Block\Adminhtml\Theme\Edit\Tab\Config
     */
    public function setSection(\Magento\Config\Model\Config\Structure\Element\Section $section){
        $this->_section = $section;
        return $this;
    }
    
    /**
     * Set section id
     * 
     * @param unknown $sectionId
     */
    public function setSectionId($sectionId){
        $this->_sectionId = $sectionId;
        return $this;
    }
    
    /**
     * Get Tab Url
     */
    public function getTabUrl(){
        if($this->getRequest()->getParam('section') == $this->_sectionId) return '#';
        $url = $this->getUrl("*/*/*",['section' => $this->_sectionId,'id' => $this->getRequest()->getParam('id')]);
        return 'javascript: if(confirm(\''.__("Please confirm scope switching. All data that hasn\'t been saved will be lost.").'\')) setLocation(\''.$url.'\');';
    }
    
    /**
     * @return Form
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareForm()
    {
        if(!$this->_section) return parent::_prepareForm();
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();             
        $this->_initObjects();
        foreach ($this->_section->getChildren() as $group) {
			if(in_array($group->getPath(), $this->helper->getNotAllowedConfigGroupPaths())){
				continue;
			}
            $this->_initGroup($group, $this->_section, $form);
        }
        $this->_eventManager->dispatch('ves_vendors_custom_theme_edit_form_prepare_after', ['form' => $form, 'tab' => $this]);
        $this->setForm($form);

        return parent::_prepareForm();
    }
    
    /**
     * Initialize objects required to render config form
     *
     * @return $this
     */
    protected function _initObjects()
    {
        $this->_fieldsetRenderer = $this->_fieldsetFactory->create();
        $this->_fieldRenderer = $this->_fieldFactory->create();
        return $this;
    }
    
    /**
     * Initialize config field group
     *
     * @param \Magento\Config\Model\Config\Structure\Element\Group $group
     * @param \Magento\Config\Model\Config\Structure\Element\Section $section
     * @param \Magento\Framework\Data\Form\AbstractForm $form
     * @return void
     */
    protected function _initGroup(
        \Magento\Config\Model\Config\Structure\Element\Group $group,
        \Magento\Config\Model\Config\Structure\Element\Section $section,
        \Magento\Framework\Data\Form\AbstractForm $form
    ) {
        $frontendModelClass = $group->getFrontendModel();
        $fieldsetRenderer = $frontendModelClass ? $this->_layout->getBlockSingleton(
            $frontendModelClass
        ) : $this->_fieldsetRenderer;
    
        $fieldsetRenderer->setForm($form);
        //$fieldsetRenderer->setConfigData($this->_configData);
        $fieldsetRenderer->setGroup($group);
    
        $fieldsetConfig = [
            'legend' => $group->getLabel(),
            'comment' => $group->getComment(),
            'expanded' => $group->isExpanded(),
            'group' => $group->getData(),
        ];
    
        $fieldset = $form->addFieldset($this->_generateElementId($group->getPath()), $fieldsetConfig);
        $fieldset->setRenderer($fieldsetRenderer);
        $group->populateFieldset($fieldset);
        $this->_addElementTypes($fieldset);
    
        $dependencies = $group->getDependencies($this->getStoreCode());
        $elementName = $this->_generateElementName($group->getPath());
        $elementId = $this->_generateElementId($group->getPath());
    
        $this->_populateDependenciesBlock($dependencies, $elementId, $elementName);
    
        if ($group->shouldCloneFields()) {
            $cloneModel = $group->getCloneModel();
            foreach ($cloneModel->getPrefixes() as $prefix) {
                $this->initFields($fieldset, $group, $section, $prefix['field'], $prefix['label']);
            }
        } else {
            $this->initFields($fieldset, $group, $section);
        }
    
        //$this->_fieldsets[$group->getId()] = $fieldset;
    }
    
    /**
     * Return dependency block object
     *
     * @return \Magento\Backend\Block\Widget\Form\Element\Dependence
     */
    protected function _getDependence()
    {
        if (!$this->getChildBlock('element_dependence')) {
            $this->addChild('element_dependence', 'Magento\Backend\Block\Widget\Form\Element\Dependence');
        }
        return $this->getChildBlock('element_dependence');
    }
    
    /**
     * Initialize config group fields
     *
     * @param \Magento\Framework\Data\Form\Element\Fieldset $fieldset
     * @param \Magento\Config\Model\Config\Structure\Element\Group $group
     * @param \Magento\Config\Model\Config\Structure\Element\Section $section
     * @param string $fieldPrefix
     * @param string $labelPrefix
     * @return $this
     */
    public function initFields(
        \Magento\Framework\Data\Form\Element\Fieldset $fieldset,
        \Magento\Config\Model\Config\Structure\Element\Group $group,
        \Magento\Config\Model\Config\Structure\Element\Section $section,
        $fieldPrefix = '',
        $labelPrefix = ''
    ) {
//         if (!$this->_configDataObject) {
//             $this->_initObjects();
//         }
    
        // Extends for config data
        $extraConfigGroups = [];
    
        /** @var $element \Magento\Config\Model\Config\Structure\Element\Field */
        foreach ($group->getChildren() as $element) {
            if ($element instanceof \Magento\Config\Model\Config\Structure\Element\Group) {
                $this->_initGroup($element, $section, $fieldset);
            } else {
                $path = $element->getConfigPath() ?: $element->getPath($fieldPrefix);
//                 if ($element->getSectionId() != $section->getId()) {
//                     $groupPath = $element->getGroupPath();
//                     if (!isset($extraConfigGroups[$groupPath])) {
//                         $this->_configData = $this->_configDataObject->extendConfig(
//                             $groupPath,
//                             false,
//                             $this->_configData
//                         );
//                         $extraConfigGroups[$groupPath] = true;
//                     }
//                 }
								if(in_array($path, $this->helper->getNotAllowedConfigFieldPaths())){
									continue;
								}
                $this->_initElement($element, $fieldset, $path, $fieldPrefix, $labelPrefix);
            }
        }
        return $this;
    }
    
    /**
     * Initialize form element
     *
     * @param \Magento\Config\Model\Config\Structure\Element\Field $field
     * @param \Magento\Framework\Data\Form\Element\Fieldset $fieldset
     * @param string $path
     * @param string $fieldPrefix
     * @param string $labelPrefix
     * @return void
     */
    protected function _initElement(
        \Magento\Config\Model\Config\Structure\Element\Field $field,
        \Magento\Framework\Data\Form\Element\Fieldset $fieldset,
        $path,
        $fieldPrefix = '',
        $labelPrefix = ''
    ) {
        $inherit = true;
        $theme = $this->getTheme();
        $themeConfig = $theme->getAllConfigs();
        
        $data = '';
        if ($field->getConfigPath() !== null) {
            $path = $field->getConfigPath();
        }
        if(isset($themeConfig[$path])){
            $data = $themeConfig[$path];
            $inherit = false;
        }else{
            $data = $this->_scopeConfig->getValue($path);
        }
        if ($field->hasBackendModel()) {
            $backendModel = $field->getBackendModel();
            $backendModel->setPath($path)
            ->setValue($data)
            ->setWebsite('')
            ->setStore('')
            ->afterLoad();
            $data = $backendModel->getValue();
        }
        
        
        $fieldRendererClass = $field->getFrontendModel();

        
        if ($fieldRendererClass) {
            $fieldRenderer = $this->_layout->getBlockSingleton($fieldRendererClass);
        } else {
            $fieldRenderer = $this->_fieldRenderer;
        }
    
        //$fieldRenderer->setForm($this->getForm());

        $elementName = $this->_generateElementName($field->getPath(), $fieldPrefix);
        $elementId = $this->_generateElementId($field->getPath($fieldPrefix));
    
        $dependencies = $field->getDependencies($fieldPrefix, $this->getStoreCode());
        $this->_populateDependenciesBlock($dependencies, $elementId, $elementName);
    
        $sharedClass = $this->_getSharedCssClass($field);
        $requiresClass = $this->_getRequiresCssClass($field, $fieldPrefix);
    
        $isReadOnly = false;
        $formField = $fieldset->addField(
            $elementId,
            $field->getType(),
            [
                'name' => $elementName,
                'label' => $field->getLabel($labelPrefix),
                'comment' => $field->getComment($data),
                'tooltip' => $field->getTooltip(),
                'hint' => $field->getHint(),
                'value' => $data,
                'inherit' => $inherit,
                'class' => $field->getFrontendClass() . $sharedClass . $requiresClass,
                'field_config' => $field->getData(),
                'scope' => $this->getScope(),
                'scope_id' => $this->getScopeId(),
                'scope_label' => $this->getScopeLabel($field),
                'can_use_default_value' => true,
                'can_use_website_value' => false,
                'can_restore_to_default' => false,
                'disabled' => $isReadOnly,
                'is_disable_inheritance' => $isReadOnly
            ]
        );

        
        $field->populateInput($formField);
    
        if ($field->hasValidation()) {
            $formField->addClass($field->getValidation());
        }
        if ($field->getType() == 'multiselect') {
            $formField->setCanBeEmpty($field->canBeEmpty());
        }
        if ($field->hasOptions()) {
            $formField->setValues($field->getOptions());
        }
        $formField->setRenderer($fieldRenderer);
    }
    
    /**
     * Get additional element types
     *
     * @return array
     */
    protected function _getAdditionalElementTypes()
    {
        return [
            'allowspecific' => 'Magento\Config\Block\System\Config\Form\Field\Select\Allowspecific',
            'image' => 'Magento\Config\Block\System\Config\Form\Field\Image',
            'file' => 'Magento\Config\Block\System\Config\Form\Field\File'
        ];
    }
    
    /**
     * Get theme
     * 
     * @return \Vnecoms\VendorsCustomTheme\Model\Theme
     */
    public function getTheme(){
        return $this->_coreRegistry->registry('current_theme');
    }
    
    /**
     * Retrieve current scope
     *
     * @return string
     */
    public function getScope()
    {
        $scope = $this->getData('scope');
        if ($scope === null) {
            $scope = self::SCOPE_DEFAULT;
            $this->setScope($scope);
        }
    
        return $scope;
    }
    
    /**
     * Get current scope code
     *
     * @return int|string
     */
    public function getScopeId()
    {
        $scopeId = 0;
        return $scopeId;
    }
    
    /**
     * Get css class for "shared" functionality
     *
     * @param \Magento\Config\Model\Config\Structure\Element\Field $field
     * @return string
     */
    protected function _getSharedCssClass(\Magento\Config\Model\Config\Structure\Element\Field $field)
    {
        $sharedClass = '';
        if ($field->getAttribute('shared') && $field->getConfigPath()) {
            $sharedClass = ' shared shared-' . str_replace('/', '-', $field->getConfigPath());
            return $sharedClass;
        }
        return $sharedClass;
    }
    
    /**
     * Get css class for "requires" functionality
     *
     * @param \Magento\Config\Model\Config\Structure\Element\Field $field
     * @param string $fieldPrefix
     * @return string
     */
    protected function _getRequiresCssClass(\Magento\Config\Model\Config\Structure\Element\Field $field, $fieldPrefix)
    {
        $requiresClass = '';
        $requiredPaths = array_merge($field->getRequiredFields($fieldPrefix), $field->getRequiredGroups($fieldPrefix));
        if (!empty($requiredPaths)) {
            $requiresClass = ' requires';
            foreach ($requiredPaths as $requiredPath) {
                $requiresClass .= ' requires-' . $this->_generateElementId($requiredPath);
            }
            return $requiresClass;
        }
        return $requiresClass;
    }
    
    /**
     * Populate dependencies block
     *
     * @param array $dependencies
     * @param string $elementId
     * @param string $elementName
     * @return void
     */
    protected function _populateDependenciesBlock(array $dependencies, $elementId, $elementName)
    {
        foreach ($dependencies as $dependentField) {
            /** @var $dependentField \Magento\Config\Model\Config\Structure\Element\Dependency\Field */
            $fieldNameFrom = $this->_generateElementName($dependentField->getId(), null, '_');
            $this->_getDependence()->addFieldMap(
                $elementId,
                $elementName
            )->addFieldMap(
                $this->_generateElementId($dependentField->getId()),
                $fieldNameFrom
            )->addFieldDependence(
                $elementName,
                $fieldNameFrom,
                $dependentField
            );
        }
    }
    
    
    /**
     * Generate element name
     *
     * @param string $elementPath
     * @param string $fieldPrefix
     * @param string $separator
     * @return string
     */
    protected function _generateElementName($elementPath, $fieldPrefix = '', $separator = '/')
    {
        $part = explode($separator, $elementPath);
        array_shift($part);
        //shift section name
        $fieldId = array_pop($part);
        //shift filed id
        $groupName = implode('][groups][', $part);
        $name = 'groups[' . $groupName . '][fields][' . $fieldPrefix . $fieldId . '][value]';
        return $name;
    }
    
    /**
     * Generate element id
     *
     * @param string $path
     * @return string
     */
    protected function _generateElementId($path)
    {
        return str_replace('/', '_', $path);
    }
    
    public function toHtml(){
        if($this->getRequest()->getParam('section') != $this->_section->getId()) return '';
        return parent::toHtml();
    }
}
