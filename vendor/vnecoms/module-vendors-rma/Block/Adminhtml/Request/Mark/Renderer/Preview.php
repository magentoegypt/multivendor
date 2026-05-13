<?php
namespace Vnecoms\VendorsRMA\Block\Adminhtml\Request\Mark\Renderer;

use Magento\Backend\Block\Widget\Form\Renderer\Fieldset\Element;
use Magento\Framework\Registry;

class Preview extends Element
{
    /**
     * get Use Custom Message of all template
     * @return array
     */
    public function getOptionVendorTemplate(){
        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $customMessage = $object_manager->get('\Vnecoms\VendorsRMA\Model\Request\Escalate\Template')
            ->getCustomerMessage();
        return json_encode($customMessage);
    }

}