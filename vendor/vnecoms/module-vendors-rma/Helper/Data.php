<?php
namespace Vnecoms\VendorsRMA\Helper;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Helper\View as CustomerViewHelper;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\ObjectManager;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_ESCALATE_EXPIRY_DAY = 'rma/general/max_escalate_time';
    const XML_PATH_CONTACTS_REFUND_AMOUT  = 'rma/contacts/refund_price_change';

    /**
     * get config max_escalate_time
     *
     * @return Ambigous <mixed, string, NULL, multitype:, multitype:Ambigous <string, multitype:, NULL> >
     */
    public function getMaximumTimeEscalateRequest(){
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue(self::XML_PATH_ESCALATE_EXPIRY_DAY,$storeScope);
    }

    /**
     * get config contacts_address
     *
     * @return Ambigous <mixed, string, NULL, multitype:, multitype:Ambigous <string, multitype:, NULL> >
     */
    public function emailTemplateChangeRefundAmount(){
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue(self::XML_PATH_CONTACTS_REFUND_AMOUT,$storeScope);
    }



}