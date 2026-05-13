<?php

namespace Vnecoms\VendorsCms\Block\Rule;

class Editable extends \Magento\Rule\Block\Editable
{
    /**
     * Render element.
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     *
     * @return string
     *
     * @see RendererInterface::render()
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $element->addClass('element-value-changer');
        $valueName = $element->getValueName();

        if ($valueName === '') {
            $valueName = '...';
        }

        if ($element->getShowAsText()) {
            $html = ' <input type="hidden" class="hidden" id="'.
                $element->getHtmlId().
                '" name="'.
                $element->getName().
                '" value="'.
                $element->getValue().
                '" data-form-part="'.
                $element->getData('data-form-part').
                '"/> '.
                htmlspecialchars(
                    $valueName
                ).'&nbsp;';
        } else {
            $html = ' <span class="rule-param"'.
                ($element->getParamId() ? ' id="'.
                    $element->getParamId().
                    '"' : '').
                '>'.
                '<a href="javascript:void(0)" class="label">';

            if ($this->inlineTranslate->isAllowed()) {
                $html .= $this->escapeHtml($valueName);
            } else {
                $html .= $this->escapeHtml(
                    $this->filterManager->truncate($valueName, ['length' => 33, 'etc' => '...'])
                );
            }

            $html .= '</a><span class="element"> '.$element->getElementHtml();

            if ($element->getExplicitApply()) {
                $html .= ' <a href="javascript:void(0)" class="rule-param-apply"><img src="'.$this->getViewFileUrl(
                    'Vnecoms_VendorsCms::images/rule_component_apply.gif'
                ).'" class="v-middle" alt="'.__(
                    'Apply'
                ).'" title="'.__(
                    'Apply'
                ).'" /></a> ';
            }

            $html .= '</span></span>&nbsp;';
        }

        return $html;
    }
}
