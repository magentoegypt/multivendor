<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\Config\Source;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Config\Block\System\Config\Form\Field;

class Note extends Field
{
    /**
     * Render element value
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _renderValue(AbstractElement $element)
    {
        $html = '';
        if ($element->getComment()) {
            $html = '<td class="value" style="padding-top: 0">';
            $html .= '<p class="note"><span>' . $element->getComment() . '</span></p>';
            $html .= '</td>';
        }

        return $html;
    }

    /**
     * @inheritdoc
     */
    public function render(AbstractElement $element)
    {
        $html = '<td class="label"></td>';
        $html .= $this->_renderValue($element);
        $html .= $this->_renderHint($element);

        return $this->_decorateRowHtml($element, $html);
    }
}
