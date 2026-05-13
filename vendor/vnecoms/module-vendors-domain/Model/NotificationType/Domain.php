<?php
namespace Vnecoms\VendorsDomain\Model\NotificationType;

class Domain extends \Vnecoms\VendorsNotification\Model\Type\DefaultType
{    
    /**
     * @var Type code
     */
    const CODE = 'domain';
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\VendorsNotification\Model\Type\TypeInterface::getIconClass()
     */
    public function getIconClass(){
        return 'fa fa-globe text-blue';
    }
    
    /**
     * Get order URL
     * @see \Vnecoms\VendorsNotification\Model\Type\TypeInterface::getUrl()
     */
    public function getUrl(){
        return $this->_urlBuilder->getUrl('config/index/edit',['section' => 'page']);
    }
}
