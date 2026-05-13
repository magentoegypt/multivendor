<?php
namespace Vnecoms\VendorsRMA\Block\Vendor\Escalate\Renderer;

use Magento\Backend\Block\Widget\Form\Renderer\Fieldset\Element;
use Magento\Framework\Registry;

class Attachment extends Element
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
     * get extension Upload Html
     */
    public function getUploaderExtensionNote() {
        return $this->_helper->allowFileExtension();
    }



}