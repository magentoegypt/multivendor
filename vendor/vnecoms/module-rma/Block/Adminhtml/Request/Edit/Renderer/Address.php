<?php
namespace Vnecoms\RMA\Block\Adminhtml\Request\Edit\Renderer;

use Magento\Backend\Block\Widget\Form\Renderer\Fieldset\Element;
use Magento\Framework\Registry;

class Address extends Element
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
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Vnecoms\RMA\Helper\Config $helper,
        Registry $coreRegistry,
        array $data = []
    ) {
    
        parent::__construct($context, $data);
        $this->_coreRegistry = $coreRegistry;
        $this->_helper = $helper;
    }

    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $button = $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button')
            ->setData([
                "type"=>"button",
                'label' => __('Update Address'),
                'id'   => 'rma-update-address',
                'class' => 'primary ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only center'
            ]);
        $button->setName('send_message');
        $html = "<div class=\"admin__field field field-button \" data-ui-id=\"adminhtml-request-edit-tabs-address-0-fieldset-element-form-field-postcode\">";
        $html .= "<label class=\"label admin__field-label\" data-ui-id=\"adminhtml-request-edit-tabs-address-0-fieldset-element-text-address-postcode-label\"></label>";
        $html .= "<div class=\"admin__field-control control\">";
        $html .= $button->toHtml();
        $html .= "</div>";
        $html .= "</div>";
        return $html;
    }
}
