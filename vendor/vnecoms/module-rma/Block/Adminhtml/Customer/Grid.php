<?php
namespace Vnecoms\RMA\Block\Adminhtml\Customer;

class Grid extends \Magento\Backend\Block\Widget\Grid
{
    protected function _prepareCollection()
    {
        $registry =  \Magento\Framework\App\ObjectManager::getInstance()->get(
            'Magento\Framework\Registry'
        );
        if ($this->getCollection() && $registry->registry("customer_email")) {
            $customerEmail = $registry->registry("customer_email");
            $this->getCollection()->addFieldToFilter("customer_email", $customerEmail);
        }

        if ($this->getCollection() && $registry->registry("request_id")) {
            $request_id = $registry->registry("request_id");
            $this->getCollection()->addFieldToFilter("entity_id", ["neq"=>$request_id]);
        }

        return parent::_prepareCollection();
    }
}
