<?php
namespace Accept\Payments\Block\Adminhtml;

/**
 * @used-by etc\system.xml
 */

use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;

class SymplCallback extends Field
{    
    protected function _getElementHtml(AbstractElement $element)
    {
        $callback_url = $this->base_url . 'accept/callback/sympl';
        $html = "<div> 
                    <p><b>Transaction Processed Callback:</b><br/>$callback_url</p>
                    <p><b>Transaction Response Callback:</b><br/>$callback_url</p>
                </div>";
        return $html;
    }

    protected function _renderScopeLabel(AbstractElement $element)
    {
        $html = ' data-config-scope="';
        if ($element->getScope()) {
            $html .= $element->getScopeLabel();
        }
        $html .= '"';
        return $html;
    }
}
