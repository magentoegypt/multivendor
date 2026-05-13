<?php
namespace Vnecoms\VendorsRMA\Model\Notification\Type;

class Rma extends \Vnecoms\VendorsNotification\Model\Type\DefaultType
{
    /**
     * @var Type code
     */
    const CODE = 'rma';

    /**
     * (non-PHPdoc)
     * @see \Vnecoms\VendorsNotification\Model\Type\TypeInterface::getIconClass()
     */
    public function getIconClass(){
        return 'fa fa-shopping-cart text-red';
    }

    /**
     * Get order URL
     * @see \Vnecoms\VendorsNotification\Model\Type\TypeInterface::getUrl()
     */
    public function getUrl(){
        try{
            $additionalInfo = unserialize($this->_notification->getAdditionalInfo());
            $requestId = isset($additionalInfo['id'])?$additionalInfo['id']:0;
            return $requestId?$this->_urlBuilder->getUrl('vrma/request/view',['request_id' => $requestId]):$this->_urlBuilder->getUrl('vrma/request');
        }catch (\Exception $e){
            return parent::getUrl();
        }
    }
}
