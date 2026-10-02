<?php
declare(strict_types=1);
namespace MagentoEgypt\Fulfillment\Controller\Adminhtml\Manage;
class Save extends \Magento\Backend\App\Action implements \Magento\Framework\App\Action\HttpPostActionInterface
{
    public const ADMIN_RESOURCE='MagentoEgypt_Fulfillment::manage';
    public function __construct(\Magento\Backend\App\Action\Context $context,
        private \Magento\Sales\Api\OrderRepositoryInterface $orders,
        private \MagentoEgypt\Fulfillment\Model\Workflow $workflow,
        private \MagentoEgypt\Fulfillment\Model\Finance $finance,
        private \Magento\Backend\Model\Auth\Session $auth) { parent::__construct($context); }
    public function execute()
    {
        try {
            $p=$this->getRequest()->getPostValue(); $order=$this->orders->get((int)($p['order_id']??0));
            if (($p['action']??'')==='progress') {
                $this->workflow->transition($order,(string)$p['group_id'],(string)$p['state'],(string)$p['reference']);
            } else {
                if (!$this->_authorization->isAllowed('MagentoEgypt_Fulfillment::finance')) throw new \DomainException('Finance permission required.');
                $raw=(string)($p['amount_minor']??'');
                if (!preg_match('/^[1-9][0-9]{0,8}$/D',$raw)) throw new \DomainException('Enter a positive amount in minor units.');
                if (($p['action']??'')==='cost') $this->finance->cost($order,(string)$p['group_id'],(int)$raw,(string)$p['reference'],(int)$this->auth->getUser()->getId());
                elseif (($p['action']??'')==='payout') $this->finance->payout($order,(int)$p['vendor_id'],(int)$raw,(string)$p['reference'],(int)$this->auth->getUser()->getId());
                elseif (($p['action']??'')==='receipt') $this->finance->receipt($order,(int)$raw,(string)$p['reference'],(int)$this->auth->getUser()->getId());
                else throw new \DomainException('Unknown operation.');
            }
            $this->messageManager->addSuccessMessage(__('Operation recorded.'));
        } catch (\Throwable $e) { $this->messageManager->addErrorMessage(__('Operation rejected: %1',$e->getMessage())); }
        return $this->resultRedirectFactory->create()->setPath('*/*/index');
    }
}
