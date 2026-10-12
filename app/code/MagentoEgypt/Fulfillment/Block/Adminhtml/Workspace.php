<?php
declare(strict_types=1);
namespace MagentoEgypt\Fulfillment\Block\Adminhtml;
class Workspace extends \Magento\Backend\Block\Template
{
    public function __construct(\Magento\Backend\Block\Template\Context $context,
        private \Magento\Framework\App\ResourceConnection $resource,
        private \MagentoEgypt\Fulfillment\Model\Workflow $workflow,
        private \Magento\Framework\AuthorizationInterface $authorization,
        array $data=[]) { parent::__construct($context,$data); }
    public function orders(): array
    {
        $db=$this->resource->getConnection();
        return $db->fetchAll($db->select()->from($this->resource->getTableName('sales_order'),['entity_id','increment_id','base_currency_code','hf_plan_json'])
            ->where('hf_plan_json IS NOT NULL')->order('entity_id DESC')->limit(50));
    }
    public function journal(int $id): array
    {
        $db=$this->resource->getConnection();
        return $db->fetchAll($db->select()->from($this->resource->getTableName('me_fulfillment_journal'))->where('order_id = ?',$id)->order('journal_id DESC')->limit(100));
    }
    public function state(int $id,array $group): string { return $this->workflow->state($id,$group); }
    public function financeAllowed(): bool { return $this->authorization->isAllowed('MagentoEgypt_Fulfillment::finance'); }
    public function formKeyHtml(): string { return $this->getLayout()->createBlock(\Magento\Framework\View\Element\FormKey::class)->toHtml(); }
}
