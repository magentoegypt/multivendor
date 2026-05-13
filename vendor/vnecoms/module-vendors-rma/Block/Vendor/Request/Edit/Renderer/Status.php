<?php
namespace Vnecoms\VendorsRMA\Block\Vendor\Request\Edit\Renderer;

use Vnecoms\Vendors\Block\Vendors\Widget\Form\Renderer\Element;
use Magento\Framework\Registry;

class Status extends Element
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
     * @var Ticket
     */
    protected $_rma = null;

    /**
     * Vnecoms/RMA/Helper/Data
     *
     * @var helper
     */
    protected $_helper;
    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Vnecoms\RMA\Helper\Config $helper,
        Registry $coreRegistry,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->_coreRegistry = $coreRegistry;
        $this->_helper = $helper;
    }
    /**
     * get Curent Request Data
     * @return mixed
     */
    public function getRequestRMA() {
        return $this->_coreRegistry->registry("current_request");
    }
    /**
     * get Status history of ticket
     * @return mixed
     */
    public function getRequestStatusHistory() {
        return $this->getRequestRMA()->getAllStatusHistoryFromRequest();
    }
    /**
     * format date html
     * @param $date
     * @return mixed
     */
    public function getFormatDateHtml($date) {
        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $dateObj = $object_manager->get('\Magento\Framework\Stdlib\DateTime\DateTime');
        return $dateObj->date('F j, Y, g:i a',$date);
    }

    /**
     * get Prefix Html
     * @param $type
     * @return string
     */
    public function getPrefixChangeBy($type)
    {
        $prefix="[A]";
        switch ($type) {
            case \Vnecoms\RMA\Model\Source\Message\Type::CHANGE_BY_DEPARTMENT:
                $prefix= '<span style="color:green">'.__("[Admin]").'</span>';
                break;
            case \Vnecoms\RMA\Model\Source\Message\Type::CHANGE_BY_CUSTOMER:
                $prefix= '<span style="color:red">'.__("[Customer]").'</span>';
                break;
            case \Vnecoms\VendorsRMA\Model\Source\Message\Type::CHANGE_BY_VENDOR:
                $prefix= '<span style="color:blue">'.__("[Vendor]").'</span>';
                break;
        }
        return $prefix;
    }

    /**
     * get Status Title
     * @return $title
     */
    public function getStatusTitle($status) {
        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $object = $object_manager->get('\Vnecoms\RMA\Model\Status')->load($status);
        return $object->getLabelByStoreId();
    }
}
