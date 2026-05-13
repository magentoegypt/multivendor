<?php
namespace Vnecoms\VendorsRMA\Block\Vendor\Request;

class Grid extends \Vnecoms\Vendors\Block\Vendors\Widget\Grid
{

    protected function _prepareCollection()
    {

        $om = \Magento\Framework\App\ObjectManager::getInstance();
        $vendor =  $om->get('Vnecoms\Vendors\Model\Session')->getVendor();

        $registry =  \Magento\Framework\App\ObjectManager::getInstance()->get(
            'Magento\Framework\Registry'
        );

        if ($this->getCollection() && $registry->registry("customer_email")) {
            $customerEmail = $registry->registry("customer_email");
            $this->getCollection()->addFieldToFilter("customer_email", $customerEmail);
        }

        if ($this->getCollection() && $registry->registry("request_id")) {
            $request_id = $registry->registry("request_id");
            $this->getCollection()->addFieldToFilter("entity_id",array("neq"=>$request_id))
                ->addFieldToFilter("vendor_id",array("eq"=>$vendor->getId()));
        }

        return parent::_prepareCollection();
    }
}


