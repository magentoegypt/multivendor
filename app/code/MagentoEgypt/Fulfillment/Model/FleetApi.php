<?php
declare(strict_types=1);
namespace MagentoEgypt\Fulfillment\Model;

final class FleetApi implements \MagentoEgypt\Fulfillment\Api\FleetInterface
{
    public function __construct(private \Magento\Sales\Api\OrderRepositoryInterface $repository,
        private \Magento\Framework\Api\SearchCriteriaBuilder $criteria,
        private \Magento\Framework\Api\SortOrderBuilder $sorts,private Dispatch $dispatch,
        private \Magento\Authorization\Model\UserContextInterface $user) {}
    public function orders($afterId=0,$limit=50)
    {
        if ((int)$afterId<0 || (int)$limit<1 || (int)$limit>100) throw new \Magento\Framework\Exception\InputException(__('Invalid order page.'));
        $criteria=$this->criteria->addFilter('entity_id',(int)$afterId,'gt')->addFilter('is_virtual',0)
            ->setSortOrders([$this->sorts->setField('entity_id')->setDirection('ASC')->create()])->setPageSize((int)$limit)->setCurrentPage(1)->create();
        $items=[];$last=(int)$afterId;
        foreach($this->repository->getList($criteria)->getItems() as $order) {$items[]=$this->dispatch->describe($order);$last=(int)$order->getId();}
        return json_encode(['contract_version'=>1,'orders'=>$items,'next_after_id'=>$last,'has_more'=>count($items)===(int)$limit],JSON_THROW_ON_ERROR);
    }
    public function order($orderId) {return json_encode($this->dispatch->describe($this->repository->get((int)$orderId)),JSON_THROW_ON_ERROR);}
    public function event($payloadJson)
    {
        try {
            if (!is_string($payloadJson) || strlen($payloadJson)>12000)throw new \DomainException('Invalid dispatch event.');
            $input=json_decode($payloadJson,true,16,JSON_THROW_ON_ERROR);
            return json_encode($this->dispatch->apply($this->repository->get((int)($input['order_id']??0)),$payloadJson,null,'api:'.$this->user->getUserType().':'.$this->user->getUserId()),JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) { throw new \Magento\Framework\Exception\InputException(__('Invalid dispatch JSON.')); }
        catch (\DomainException $e) {
            $conflict=str_contains($e->getMessage(),'version changed') || str_contains($e->getMessage(),'Operation key');
            throw new \Magento\Framework\Webapi\Exception(__($e->getMessage()),0,$conflict?409:400);
        }
    }
}
