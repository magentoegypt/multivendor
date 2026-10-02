<?php
declare(strict_types=1);
namespace MagentoEgypt\Fulfillment\Controller\Vendors\Manage;

class Save extends \MagentoEgypt\Fulfillment\Controller\Vendors\Manage implements \Magento\Framework\App\Action\HttpPostActionInterface
{
    public function execute()
    {
        try {
            $id=$this->ownerId();
            if (!$this->formKeys->validate($this->getRequest())) throw new \DomainException('The form expired. Reload and retry.');
            if ($this->getRequest()->getParam('action')==='dispatch') {
                $p=$this->getRequest()->getPostValue();
                $payload=['order_id'=>(int)$p['order_id'],'group_id'=>(string)$p['group_id'],'action'=>(string)$p['dispatch_action'],
                    'expected_version'=>(int)$p['expected_version'],'operation_key'=>(string)$p['operation_key']];
                foreach (['execution','responsibility','carrier','tracking','note'] as $key) if(isset($p[$key]))$payload[$key]=(string)$p[$key];
                $this->dispatch->apply($this->orders->get($payload['order_id']),json_encode($payload,JSON_THROW_ON_ERROR),$id,'customer:'.$this->_session->getCustomerId());
            } elseif ($this->getRequest()->getParam('action')==='progress') {
                $this->workflow->transition($this->orders->get((int)$this->getRequest()->getParam('order_id')),
                    (string)$this->getRequest()->getParam('group_id'),(string)$this->getRequest()->getParam('state'),
                    (string)$this->getRequest()->getParam('operation_key'),$id);
            } else {
                $this->api->saveForVendor($id,(string)$this->getRequest()->getParam('policy_json'));
            }
            $this->getMessageManager()->addSuccessMessage(__('Saved.'));
        } catch (\Throwable $e) { $this->getMessageManager()->addErrorMessage(__('Could not save: %1',$e->getMessage())); }
        if ((int)$this->getRequest()->getParam('vendor_order_id')>0) return $this->_redirect('sales/order/view',['order_id'=>(int)$this->getRequest()->getParam('vendor_order_id')]);
        return $this->_redirect('hubfulfillment/manage/index');
    }
}
