<?php
namespace MagentoEgypt\CityManager\Block\Adminhtml;
class Locations extends \Magento\Backend\Block\Template
{
    public function __construct(\Magento\Backend\Block\Template\Context $context, private \MagentoEgypt\CityManager\Model\Directory $cityDirectory, array $data=[]) {parent::__construct($context,$data);}
    public function record(): array { return $this->cityDirectory->get((int)$this->getRequest()->getParam('id')); }
    public function choices(string $country, string $level): array {return $this->cityDirectory->options($country,$level);}
    public function rows(): array { return $this->cityDirectory->options((string)$this->getRequest()->getParam('country','EG'),(string)$this->getRequest()->getParam('level','region'),(int)$this->getRequest()->getParam('parent',0),0,(string)$this->getRequest()->getParam('q',''),true,100,(max(1,(int)$this->getRequest()->getParam('page',1))-1)*100); }
}
