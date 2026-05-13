<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Block\Adminhtml\Request\Edit;

use Magento\Framework\Registry;

class TabsLeft extends \Magento\Backend\Block\Template
{
    /**
     * Core registry
     *
     * @var Registry
     */
    protected $_coreRegistry = null;
    /**
     * Vnecoms/RMA/Model/Request
     *
     * @var Request
     */
    protected $_request = null;

    /**
     * @var \Vnecoms\RMA\Helper\Config
     */
    protected $_requestHelper;


    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        Registry $coreRegistry,
        \Vnecoms\RMA\Helper\Config $requestHelper,
        array $data = []
    ) {
    
        parent::__construct($context, $data);
        $this->_coreRegistry = $coreRegistry;
        $this->_requestHelper = $requestHelper;
    }
    /**
     * get Label Tab Request Information
     * @return mixed
     */
    public function getTitleRequestTab()
    {
        return __('RMA Information');
    }

    /**
     * get Label Tab Customer Information
     * @return mixed
     */
    public function getTitleCustomerTab()
    {
        return __('Customer Infomation');
    }

    /**
     * get Curent Reques RMA Data
     * @return mixed
     */
    public function getRequestRma()
    {
        return $this->_coreRegistry->registry("current_request");
    }

    /**
     * get Customer email of curent RMA
     * @return mixed
     */

    public function getCustomerEmail()
    {
        return $this->getRequestRma()->getData('customer_email');
    }

    /**
     * get Status class
     * @return mixed
     */
    public function getStatusClass()
    {
        $class = "";
        switch ($this->getRequestRma()->getStatusObject()->getCode()) {
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
     * get Type class
     * @return mixed
     */
    public function getTypeClass()
    {
        if ($this->getRequestRma()->getType() == "replace") {
            return "type type-replace";
        }
        return "type type-refund";
    }

    /**
     * format date html
     * @param $date
     * @return mixed
     */
    public function getFormatDateHtml($date)
    {
        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $dateObj = $object_manager->get('\Magento\Framework\Stdlib\DateTime\DateTime');
        return $dateObj->date('F j, Y, g:i a', $date);
    }
    /**
     * get GEO IP
     */

    public function getGeoIp()
    {
        $ip = $this->getRequestRma()->getData('ip_address');
        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $geo = $object_manager->get('\Vnecoms\RMA\Helper\Geoip');
        $geo->setRecord($ip);
        return $geo;
    }

    /**
     * check state pending
     * @return bool
     */
    public function isEnableEditTabs()
    {
        if ($this->getRequestRma()->getState() == \Vnecoms\RMA\Model\Request::STATE_OPEN) {
            return true;
        }
        return false;
    }
}
