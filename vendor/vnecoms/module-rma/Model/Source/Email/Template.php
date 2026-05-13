<?php

namespace Vnecoms\RMA\Model\Source\Email;

class Template extends \Magento\Framework\DataObject
{
    /**
     * Config xpath to email template node
     *
     */
    const XML_PATH_TEMPLATE_EMAIL = 'global/template/email/';


    protected $_scopeConfig;
    protected $_emailTemplate;

    /**
     * Template constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Email\Model\ResourceModel\Template\Collection $email
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Email\Model\ResourceModel\Template\Collection $email,
        \Magento\Email\Model\Template\Config $emailConfig
    ) {
    
        $this->_scopeConfig = $scopeConfig;
        $this->_emailTemplate = $email;
        $this->_emailConfig = $emailConfig;
        parent::__construct();
    }

    /**
     * @param $nodeName
     * @return template transaction email
     */

    public function getEmailTemplate($nodeName)
    {
        $templateLabel = $this->_emailConfig->getTemplateLabel($nodeName);
        $templateLabel = __('%1 (Default)', $templateLabel);
        $templates = [$nodeName => $templateLabel];
        $options = $this->_emailTemplate->toOptionArray();
        foreach ($options as $option) {
            $templates[$option["value"]] = $option["label"];
        }

        return $templates;
    }


    /**
     * @param $nodeName
     * @return template transaction email
     */

    public function getEmailTemplateToOptions($nodeName)
    {

        $templateLabel = $this->_emailConfig->getTemplateLabel($nodeName);
        $templateLabel = __('%1 (Default)', $templateLabel);
        $templates = ["label"=>$templateLabel,"value" => $nodeName];
        $options = $this->_emailTemplate->toOptionArray();
        //  var_dump($templates);exit;
        array_unshift($options, $templates);
        return $options;
    }
}
