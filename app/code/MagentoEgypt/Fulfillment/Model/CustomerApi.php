<?php
declare(strict_types=1);
namespace MagentoEgypt\Fulfillment\Model;
final class CustomerApi implements \MagentoEgypt\Fulfillment\Api\CustomerInterface
{
    public function __construct(private \Magento\Authorization\Model\UserContextInterface $user,
        private \Magento\Sales\Api\OrderRepositoryInterface $orders, private Preview $preview, private Workflow $workflow,
        private \Magento\Framework\Api\SearchCriteriaBuilder $criteria) {}
    public function byNumber($number)
    {
        if ($this->user->getUserType()!==\Magento\Authorization\Model\UserContextInterface::USER_TYPE_CUSTOMER || !$this->user->getUserId()) {
            throw new \Magento\Framework\Exception\AuthorizationException(__('Order access denied.'));
        }
        $criteria=$this->criteria->addFilter('increment_id',(string)$number)->addFilter('customer_id',(int)$this->user->getUserId())->setPageSize(1)->create();
        $items=$this->orders->getList($criteria)->getItems();
        if (!$items) throw new \Magento\Framework\Exception\NoSuchEntityException(__('Order not found.'));
        return $this->order((int)reset($items)->getEntityId());
    }
    public function order($orderId)
    {
        $order=$this->orders->get((int)$orderId);
        if ($this->user->getUserType()!==\Magento\Authorization\Model\UserContextInterface::USER_TYPE_CUSTOMER
            || !$this->user->getUserId() || (int)$order->getCustomerId()!==(int)$this->user->getUserId()) {
            throw new \Magento\Framework\Exception\AuthorizationException(__('Order access denied.'));
        }
        if (!$order->getData('hf_plan_json')) return json_encode(['status'=>'legacy_order']);
        $plan=json_decode($order->getData('hf_plan_json'),true,64,JSON_THROW_ON_ERROR);
        foreach ($plan['groups'] as &$g) $g['state']=$this->workflow->state((int)$order->getId(),$g);
        unset($g); $plan=$this->preview->publicPlan($plan); unset($plan['checkout_binding']);
        $plan['status']='ordered';
        return json_encode($plan,JSON_THROW_ON_ERROR);
    }
}
