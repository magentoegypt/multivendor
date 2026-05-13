<?php
namespace Vnecoms\VendorsNotification\Model\Type;

use Vnecoms\VendorsNotification\Model\Type\TypeInterface;
use Vnecoms\VendorsNotification\Model\Notification;
use Vnecoms\Vendors\Model\UrlInterface;

class DefaultType implements TypeInterface
{

    /**
     * @var \Vnecoms\VendorsNotification\Model\Notification
     */
    protected $_notification;
    
    /**
     * @var \Vnecoms\Vendors\Model\UrlInterface
     */
    protected $_urlBuilder;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    protected $serializer;

    /**
     * DefaultType constructor.
     * @param Notification $notification
     * @param UrlInterface $urlBuilder
     * @param \Magento\Framework\Serialize\Serializer\Serialize $serializer
     */
    public function __construct(
        Notification $notification,
        UrlInterface $urlBuilder,
        \Magento\Framework\Serialize\Serializer\Serialize $serializer
    ) {
        $this->_notification = $notification;
        $this->_urlBuilder = $urlBuilder;
        $this->serializer = $serializer;
    }

    /**
     * (non-PHPdoc)
     * @see \Vnecoms\VendorsNotification\Model\Type\TypeInterface::getMessage()
     */
    public function getMessage()
    {
        return $this->_notification->getMessage();
    }
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\VendorsNotification\Model\Type\TypeInterface::getIconClass()
     */
    public function getIconClass()
    {
        return 'fa fa-envelope text-red';
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\VendorsNotification\Model\Type\TypeInterface::getUrl()
     */
    public function getUrl()
    {
        return '#';
    }
}
