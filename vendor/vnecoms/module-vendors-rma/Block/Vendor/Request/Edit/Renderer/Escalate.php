<?php
namespace Vnecoms\VendorsRMA\Block\Vendor\Request\Edit\Renderer;

use Magento\Backend\Block\Widget\Form\Renderer\Fieldset\Element;
use Magento\Framework\Registry;
use Vnecoms\VendorsRMA\Model\Source\Message\Type as MESSAGE_TYPE ;

class Escalate extends Element
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
     * Vnecoms/RMA/Helper/Config
     *
     * @var Ticket
     */
    protected $_helper;

    /**
     * Vnecoms/VendorsRMA/Model/Request/Escalate
     *
     * @var Ticket
     */
    protected $_escalate = null;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Vnecoms\RMA\Helper\Config $helper,
        \Vnecoms\VendorsRMA\Model\Request\EscalateFactory $escalateFactory,
        Registry $coreRegistry,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->_coreRegistry = $coreRegistry;
        $this->_helper = $helper;
        $this->_escalate = $escalateFactory;
    }

    /**
     * get Curent Request Data
     * @return mixed
     */
    public function getRequestRma(){
        return $this->_coreRegistry->registry("current_request");
    }

    /**
     * check is show button reply
     * @return bool
     */
    public function isShowButtonReply(){
        if($this->getRequestRma()->getState() == \Vnecoms\RMA\Model\Request::STATE_CLOSED
            || $this->getRequestRma()->getState() == \Vnecoms\RMA\Model\Request::STATE_CANCELED
            ) return false;
        return true;
    }

    /**
     * get Send Name Message
     * @param $type
     * @return mixed
     */
    public function getDisplayName($type){
        $request = $this->getRequestRma();
        switch ($type){
            case \Vnecoms\RMA\Model\Source\Message\Type::TYPE_REPLY_CUSTOMER:
                $name= $request->getCustomerName();
                break;
            case \Vnecoms\RMA\Model\Source\Message\Type::TYPE_REPLY_DEPARMENT:
                $name= __("Me");
                break;
        }
        return $name;
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
     * get extension Upload Html
     */
    public function getUploaderExtensionNote() {
        return $this->_helper->allowFileExtension();
    }

    /**
     * get class for file attachment message
     * @param $file
     * @return mixed
     */
    public function getClassIcon($file){
        return  $this->_helper->getClassIcon($file);
    }

    /**
     * check Image extenstion
     */
    public function isImage($file){
        if(in_array($this->getClassIcon($file),array("icon-jpg","icon-jpeg","icon-jpeg","icon-png","icon-gif"))) return true;
        return false;
    }

    /**
     * get Escalate Object Vendor
     * @return mixed
     */
    public function getEscalateRma(){
        $escalate =   $this->_escalate->create()->getCollection()
            ->addFieldToFilter("request_id",$this->getRequestRma()->getId())
            ->addFieldToFilter("type",MESSAGE_TYPE::TYPE_REPLY_VENDOR)->getFirstItem();
        return $escalate;
    }



}