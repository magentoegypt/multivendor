<?php 
namespace MagentoEgypt\PortoExtend\Block\Adminhtml\Theme\Edit\Form;

use Magento\Framework\Data\Form\Element\AbstractElement;

class Fieldset extends \Vnecoms\VendorsCustomTheme\Block\Adminhtml\Theme\Edit\Form\Fieldset
{
	/**
     * Return children elements html.
     *
     * @param AbstractElement $element
     * @return string
     * @since 100.1.0
     */
    protected function _getChildrenElementsHtml(AbstractElement $element)
    {
        $elements = '';
        foreach ($element->getElements() as $field) {
            if ($field instanceof \Magento\Framework\Data\Form\Element\Fieldset) {
                $elements .= '<tr id="row_' . $field->getHtmlId() . '">'
                    . '<td colspan="4">' . $field->toHtml() . '</td></tr>';
            } else {
                $elements .= $field->toHtml();
                // $styleTag = $this->addVisibilityTag($field);
                // $elements .= $styleTag;
            }
        }

        return $elements;
    }
}