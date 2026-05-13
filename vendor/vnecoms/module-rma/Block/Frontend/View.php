<?php

namespace Vnecoms\RMA\Block\Frontend;

class View extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;
    /**
     * @var \Vnecoms\RMA\Helper\Config
     */
    protected $_config;

    /**
     * Core registry
     *
     * @var Registry
     */
    protected $_coreRegistry = null;

    /**
     * NewRequest constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Vnecoms\RMA\Helper\Config $config
     * @param \Vnecoms\RMA\Model\ResourceModel\Request\CollectionFactory $requestCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Vnecoms\RMA\Helper\Config $config,
        \Magento\Framework\Registry $coreRegistry,
        array $data = []
    ) {
        $this->_customerSession     = $customerSession;
        $this->_coreRegistry = $coreRegistry;
        $this->_config              = $config;
        parent::__construct($context, $data);
    }

    public function getRequestRma()
    {
        return $this->_coreRegistry->registry("current_request");
    }
    /**
     * @return \Vnecoms\RMA\Helper\Config
     */
    public function getConfig()
    {
        return $this->_config;
    }

    /**
     * @return \Vnecoms\RMA\Helper\Ambigous
     */
    public function getShowReturnInstructions()
    {
        return $this->getConfig()->enableGuide();
    }

    /**
     * @return \Vnecoms\RMA\Helper\Ambigous
     */
    public function getReturnInstructions()
    {
        return $this->getConfig()->policyBlockGuide();
    }

    /**
     * @param $state
     * @return bool
     */
    public function isReplyRma($state)
    {
        $check = false;
        if ($state == \Vnecoms\RMA\Model\Request::STATE_OPEN) {
            $check = true;
        }
        return $check;
    }

    /**
     * @param $state
     * @return bool
     */
    public function isCancelRma($state)
    {
        $check = false;
      //  if($state == \Vnecoms\RMA\Model\Request::STATE_OPEN ) $check = true;
        $status = $this->getRequestRma()->getStatusObject();
        if ($status->getCode() == \Vnecoms\RMA\Model\Request::STATUS_PENDING
            || $status->getCode() == \Vnecoms\RMA\Model\Request::STATUS_APPROVAL ) {
            $check = true;
        }
        return $check;
    }

    /**
     * @param $state
     * @return bool
     */
    public function isPrintRma($state)
    {
        $check = false;
        $status = $this->getRequestRma()->getStatusObject();
        if ($status->getCode() != \Vnecoms\RMA\Model\Request::STATUS_PENDING && $this->getConfig()->allowPrint()) {
            $check = true;
        }
        return $check;
    }

    /**
     * @param $state
     * @return bool
     */
    public function isConfirmShip($state)
    {
        $check = false;
        $status = $this->getRequestRma()->getStatusObject();
        if ($status->getCode() == \Vnecoms\RMA\Model\Request::STATUS_APPROVAL) {
            $check = true;
        }
        return $check;
    }

    /**
     * @param $state
     * @return bool
     */
    public function isResolveRma($state)
    {
        $check = false;
        if ($state == \Vnecoms\RMA\Model\Request::STATE_OPEN) {
            $check = true;
        }
        return $check;
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
