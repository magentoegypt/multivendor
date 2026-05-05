<?php 
namespace MagentoEgypt\SmsExtend\Plugin;

class Filter
{
    protected $helper;

    public function __construct(\MagentoEgypt\SmsExtend\Helper\Data $helper)
    {
        $this->helper = $helper;
    }

    public function afterFilter($subject, $sms, $template)
    {
        $this->helper->setTemplateName($template);
        return $sms;    
    }
}