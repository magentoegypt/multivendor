<?php

namespace Vnecoms\VendorsRMA\Block\Frontend;

class Escalate extends \Magento\Framework\View\Element\Template
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
        \Magento\Framework\View\Element\Template\Context $context,
        \Vnecoms\RMA\Helper\Config $helper,
        \Vnecoms\VendorsRMA\Model\Request\EscalateFactory $escalateFactory,
        \Magento\Framework\Registry $coreRegistry,
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
     * get extension Upload Html
     */
    public function getUploaderExtensionNote() {
        return $this->_helper->allowFileExtension();
    }


    /**
     * get save Escalate URL
     */
    public function getSaveNoteUrl() {
        return $this->getUrl("vrma/customer/saveEscalate");
    }


}