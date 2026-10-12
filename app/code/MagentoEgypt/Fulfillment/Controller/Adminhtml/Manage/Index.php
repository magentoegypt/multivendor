<?php
declare(strict_types=1);
namespace MagentoEgypt\Fulfillment\Controller\Adminhtml\Manage;
class Index extends \Magento\Backend\App\Action implements \Magento\Framework\App\Action\HttpGetActionInterface
{
    public const ADMIN_RESOURCE='MagentoEgypt_Fulfillment::manage';
    public function __construct(\Magento\Backend\App\Action\Context $context, private \Magento\Framework\View\Result\PageFactory $pages) { parent::__construct($context); }
    public function execute()
    {
        $page=$this->pages->create(); $page->getConfig()->getTitle()->prepend(__('Hub Fulfillment'));
        return $page;
    }
}
