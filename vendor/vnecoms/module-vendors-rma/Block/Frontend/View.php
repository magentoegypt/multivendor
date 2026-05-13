<?php

namespace Vnecoms\VendorsRMA\Block\Frontend;

class View extends \Vnecoms\RMA\Block\Frontend\View
{
    /**
     * check escalate RMA state
     * @param $request
     * @return bool
     */
    public function isEscalateRma($request){
        $check = false;
        if(
            $request->getState() != \Vnecoms\RMA\Model\Request::STATE_CANCELED
            &&  $request->canEscalate(false)
        )
            $check = true;
        return $check;
    }

    /**
     * @param $state
     * @return bool
     */
    public function isReplyRma($state){
        if(
            $state == \Vnecoms\RMA\Model\Request::STATE_OPEN ||
            $state == \Vnecoms\VendorsRMA\Model\Request::STATE_AWAITING ||
            $state == \Vnecoms\VendorsRMA\Model\Request::STATE_BEING
        ) return true;
        return false;
    }

    /**
     * get Status class
     * @return mixed
     */
    public function getStatusClass() {
        $class = "";
        switch ($this->getRequestRma()->getStatusObject()->getCode()){
            case \Vnecoms\RMA\Model\Request::STATUS_PENDING:
                $class= "status status-pending";
                break;
            case \Vnecoms\RMA\Model\Request::STATUS_APPROVAL:
                $class= "status status-approval";
                break;
            case \Vnecoms\RMA\Model\Request::STATUS_PACKSENT:
                $class= "status status-package_sent";
                break;
            case \Vnecoms\RMA\Model\Request::STATUS_CANCELED:
                $class= "status status-canceled";
                break;
            case \Vnecoms\RMA\Model\Request::STATUS_RECEIVED:
                $class= "status status-package_received";
                break;
            case \Vnecoms\VendorsRMA\Model\Request::STATUS_AWAITING:
                $class= "status status-awaiting";
                break;
            case \Vnecoms\VendorsRMA\Model\Request::STATUS_BEING:
                $class= "status status-being";
                break;
            case \Vnecoms\RMA\Model\Request::STATUS_RETURNED:
                $class= "status status-package_returned";
                break;
            case \Vnecoms\RMA\Model\Request::STATUS_RESOLVED:
                $class= "status status-resolved";
                break;
        }
        return $class;
    }
    /**
     * is enable refund tab
     * @return bool
     */
    public function isEnableRefundTab(){
        $isShow = true;
        if($this->getRequestRma()->getType() != "refund") $isShow = false;
        return $isShow;
    }

    /**
     * get Title Tab
     * @return mixed
     */
    public function getTitleRefundTab(){
        return __("Refund Amount");
    }

    /**
     * get refund amount customer object
     * @return mixed
     */
    public function getCustomerAmountRefundObject(){
        $amount = $this->getRequestRma()->getRefundAmountObject("customer");
        return $amount;
    }

    /**
     * get refund amount vendor object
     * @return mixed
     */
    public function getVendorAmountRefundObject(){
        $amount = $this->getRequestRma()->getRefundAmountObject("vendor");
        return $amount;
    }

    /**
     * get all refund amount history
     * @return mixed
     */
    public function getHistoryAmountRefundObject(){
        $amounts = $this->getRequestRma()->getRefundAmountObject("all");
        return $amounts;
    }


    /**
     * format price refund by order object
     * @param $amount
     * @return mixed
     */
    public function formatPrice($amount){
        return $this->getRequestRma()->getOrderObject()->formatPrice($amount);
    }

    /**
     * @param $type
     * @return mixed
     */
    public function getTypeHtml($type){
        if($type == "vendor") return __("Vendor");
        if($type == "admin") return __("Admin");
        return __("Customer");
    }

    /**
     * @return float|int
     */
    public function getMaxAmountRefund() {
        $amount = 0 ;
        foreach($this->getRequestRma()->getAllItemFromRequest() as $item) {
            $orderItem = \Magento\Framework\App\ObjectManager::getInstance()->get(
                'Magento\Sales\Model\Order\Item')->load($item->getOrderItemId());
            $amount += (($orderItem->getRowTotalInclTax() - $orderItem->getDiscountAmount())
                    /$orderItem->getQtyOrdered())*$item->getQty();
        }
        return $amount;
    }
    /**
     * get Vendors Config RMA
     * @return mixed
     */
    public function getShowReturnInstructions(){
        $om = \Magento\Framework\App\ObjectManager::getInstance();
        $helper = $om->get('\Vnecoms\VendorsConfig\Helper\Data');
        if ($this->getVendorId()){
            return $helper->getVendorConfig('rma/rmapolicy/show_return_instructions', $this->getVendorId());
        }
        return $this->getConfig()->enableGuide();
    }

    /**
     * get Vendors Config RMA
     * @return mixed
     */
    public function getReturnInstructions(){

        if ($this->getVendorId()) {
            $om = \Magento\Framework\App\ObjectManager::getInstance();
            $helper = $om->get('\Vnecoms\VendorsConfig\Helper\Data');
            return $helper->getVendorConfig('rma/rmapolicy/return_instructions', $this->getVendorId());
        }
        return $this->getConfig()->policyBlockGuide();
    }

    /**
     *  check Vendors Cms
     * @return true || false
     */
    public function checkVendorsCms(){
        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $module = $object_manager->get('Magento\Framework\Module\Manager');
        return $module->isEnabled("Vnecoms_VendorsCms");
    }

    /**
     *  get Vendor ID
     * @return $vendorId
     */
    public function getVendorId(){
        /*$om = \Magento\Framework\App\ObjectManager::getInstance();
        $sessionVendor = $om->get('\Vnecoms\Vendors\Model\Session');
        $vendorId = $sessionVendor->getVendor()->getId();*/
        $vendorId = $this->getRequestRma()->getVendorId();
        return $vendorId;
    }
}
