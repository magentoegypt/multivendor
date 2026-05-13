<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCategory\Block\Vendors\Category\Form\Renderer\Fieldset;

use Magento\Store\Model\ScopeInterface;

/**
 * Renderer for URL key input
 * Allows to manage and overwrite URL Rewrites History save settings.
 */
class UrlKeyRenderer extends \Vnecoms\Vendors\Block\Vendors\Widget\Form\Renderer\Fieldset\Element
{
    const XML_PATH_SEO_SAVE_HISTORY = 'catalog/seo/save_rewrites_history';

    protected $_template = 'Vnecoms_VendorsCategory::category/form/renderer/fieldset/element.phtml';

    /**
     * @var \Magento\Framework\Data\Form\Element\Factory
     */
    protected $_elementFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context      $context
     * @param \Magento\Framework\Data\Form\Element\Factory $elementFactory
     * @param array                                        $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Data\Form\Element\Factory $elementFactory,
        array $data = []
    ) {
        $this->_elementFactory = $elementFactory;
        parent::__construct($context, $data);
    }

    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        /** @var \Magento\Framework\Data\Form\Element\AbstractElement $element */
        if (!$element->getValue()) {
            return $element->getElementHtml();
        }
        $element->setOnkeyup("onUrlkeyChanged('".$element->getHtmlId()."')");
        $element->setOnchange("onUrlkeyChanged('".$element->getHtmlId()."')");

        $data = ['name' => $element->getData('name').'_create_redirect', 'disabled' => true];
        /** @var \Magento\Framework\Data\Form\Element\Hidden $hidden */
        $hidden = $this->_elementFactory->create('hidden', ['data' => $data]);
        $hidden->setForm($element->getForm());

       // $storeId = $element->getForm()->getDataObject()->getStoreId();
        $data['html_id'] = $element->getHtmlId().'_create_redirect';
        $data['label'] = __('Create Permanent Redirect for old URL');
        $data['value'] = $element->getValue();
        $data['checked'] = $this->_scopeConfig->isSetFlag(
            self::XML_PATH_SEO_SAVE_HISTORY,
            ScopeInterface::SCOPE_STORE
            //   $storeId
        );
        /** @var \Magento\Framework\Data\Form\Element\Checkbox $checkbox */
        $checkbox = $this->_elementFactory->create('checkbox', ['data' => $data]);
        $checkbox->setForm($element->getForm());

        $script = '<script>require(["Vnecoms_VendorsCategory/category/rewriteForm"], function(){});</script>';

        $html = '<div class="form-group field-meta_title" data-ui-id="vendors-category-tab-meta-0-fieldset-element-form-field-url-key">';
        $html .= '<label class="col-sm-3 control-label" for="'.$element->getHtmlId().'"><span>'.$element->getLabel().'</span></label>';
        $html .= '<div class="col-sm-9">
                    <div class="admin__field">'.$element->getElementHtml().
            '<br/>'.$hidden->getElementHtml().$checkbox->getElementHtml()
            .$checkbox->getLabelHtml().$script.'</div></div>';
        $html .= '</div>';

        return $html;
//        return $html . '<br/>' . $hidden->getElementHtml() . $checkbox->getElementHtml()
//        . $checkbox->getLabelHtml(). $script;
    }
}
