<?php

namespace Vnecoms\RMA\Ui\Component\Listing\Columns\Status\Template;

use Magento\Framework\Data\OptionSourceInterface;

class StatusCustomer implements OptionSourceInterface
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;
    
    /**
     * @var \Vnecoms\RMA\Model\System\Config\Source\Email\Template
     */
    protected $_configEmailTempalte ;

    /**
     * StatusCustomer constructor.
     * @param \Magento\Framework\Registry $registry
     * @param \Vnecoms\RMA\Model\Source\Email\Template $emailTemplate
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Vnecoms\RMA\Model\Source\Email\Template $emailTemplate
    ) {
        $this->_registry = $registry;
        $this->_configEmailTempalte = $emailTemplate;
    }


    public function toOptionArray()
    {
        return $this->_getEmailConfig();
    }

    /**
     *  get information email by key
     *
     * @return array
     */
    protected function _getEmailConfig()
    {
        $model = $this->_registry->registry('current_status');
        return $this->_configEmailTempalte->getEmailTemplateToOptions('rma_request_email_status_'.$model->getCode().'_customer');
    }
}
