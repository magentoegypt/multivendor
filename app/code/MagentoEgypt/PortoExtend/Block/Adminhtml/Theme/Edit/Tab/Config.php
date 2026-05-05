<?php
namespace MagentoEgypt\PortoExtend\Block\Adminhtml\Theme\Edit\Tab;

class Config extends \Vnecoms\VendorsCustomTheme\Block\Adminhtml\Theme\Edit\Tab\Config
{
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
        $inherit = false;
        $theme = $this->getTheme();
        $themeConfig = $theme->getAllConfigs();
        
        $data = '';
        if ($field->getConfigPath() !== null) {
            $path = $field->getConfigPath();
        }
        if(isset($themeConfig[$path])){
            $data = $themeConfig[$path];
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
}