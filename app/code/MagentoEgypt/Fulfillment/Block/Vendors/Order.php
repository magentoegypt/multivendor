<?php
declare(strict_types=1);
namespace MagentoEgypt\Fulfillment\Block\Vendors;

class Order extends \Magento\Framework\View\Element\Template
{
    public function __construct(\Magento\Framework\View\Element\Template\Context $context,
        private \Magento\Framework\Registry $registry,private \Vnecoms\Vendors\Model\Session $session,
        private \MagentoEgypt\Fulfillment\Model\Dispatch $dispatch,array $data=[]){parent::__construct($context,$data);}
    public function detail(): ?array
    {
        $order=$this->registry->registry('sales_order');$id=(int)$this->session->getVendor()->getId();
        if(!$order || !$id)return null;
        return $this->dispatch->describe($order,$id);
    }
    public function vendorOrderId(): int {return (int)$this->registry->registry('vendor_order')?->getId();}
}
