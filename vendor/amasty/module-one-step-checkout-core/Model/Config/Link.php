<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\Config;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Config\Block\System\Config\Form\Field;

class Link extends Field
{
    protected function _getElementHtml(AbstractElement $element)
    {
        $element->setHref($this->getUrl('amasty_checkout/field'));
        $confirmMessage = $this->escapeQuote($this->escapeHtml(__('Unsaved changes will be discarded.')));
        $element->setOnclick('return confirm(\'' . $confirmMessage . '\')');

        return parent::_getElementHtml($element);
    }

    protected function _renderScopeLabel(AbstractElement $element)
    {
        return '';
    }
}
