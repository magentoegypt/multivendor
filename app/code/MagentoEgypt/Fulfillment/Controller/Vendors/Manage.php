<?php
declare(strict_types=1);
namespace MagentoEgypt\Fulfillment\Controller\Vendors;

abstract class Manage extends \Vnecoms\Vendors\Controller\Vendors\Action
{
    public function __construct(\Vnecoms\Vendors\App\Action\Context $context,
        protected \MagentoEgypt\Fulfillment\Model\VendorApi $api,
        protected \Magento\Framework\App\ResourceConnection $resource,
        protected \Magento\Framework\Data\Form\FormKey\Validator $formKeys,
        protected \Magento\Sales\Api\OrderRepositoryInterface $orders,
        protected \MagentoEgypt\Fulfillment\Model\Workflow $workflow,
        protected \MagentoEgypt\Fulfillment\Model\Dispatch $dispatch)
    { parent::__construct($context); }

    protected function ownerId(): int
    {
        $id=(int)$this->_session->getVendor()->getId();
        $db=$this->resource->getConnection();
        $owner=$db->fetchOne($db->select()->from($this->resource->getTableName('ves_vendor_user'),'is_super_user')
            ->where('vendor_id = ?',$id)->where('customer_id = ?',(int)$this->_session->getCustomerId()));
        if (!$id || !$owner || (int)$this->_session->getVendor()->getStatus()!==1) {
            throw new \Magento\Framework\Exception\AuthorizationException(__('The active vendor owner account is required.'));
        }
        return $id;
    }
}
