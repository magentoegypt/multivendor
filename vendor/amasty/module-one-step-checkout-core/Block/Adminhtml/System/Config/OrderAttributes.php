<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class OrderAttributes extends Field
{
    /**
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        if ($this->getModuleManager() && $this->getModuleManager()->isEnabled('Amasty_Orderattr')) {
            $url = $this->getUrl('amorderattr/attribute/index');
            $element->setComment(__("If you want to perfrom a more complex configuration and need to have an advanced "
                . "control over your order attributes, go to the settings page of Amasty Order Attributes module by "
                . "clicking <a target='_blank' href='%1'>here</a>.", $url));
        } else {
            $url = 'https://amasty.com/order-attributes-for-magento-2.html'
                . '?utm_source=extension&utm_medium=link&utm_campaign=osc-order-attributes-m2';
            $element->setComment(__("If you need a wider range of configurable attributes available for you to "
                . "customize and fine-tune your checkout page, please get our extension 'Order Attributes'. Learn more "
                . "<a target='_blank' href='%1'>here</a>.", $url));
        }

        return parent::render($element);
    }
}
