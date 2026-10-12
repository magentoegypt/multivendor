<?php
declare(strict_types=1);
namespace MagentoEgypt\Fulfillment\Controller\Vendors\Manage;

class Index extends \MagentoEgypt\Fulfillment\Controller\Vendors\Manage
{
    public function execute()
    {
        try {
            $data=json_decode($this->api->workspaceForVendor($this->ownerId()),true,64,JSON_THROW_ON_ERROR);
            $this->_initAction();
            $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Fulfillment'));
            $layout=$this->_view->getLayout();
            $layout->createBlock(\Magento\Framework\View\Element\Template::class,'hub.fulfillment.vendor')
                ->setTemplate('MagentoEgypt_Fulfillment::vendor/workspace.phtml')->setData('workspace',$data);
            $layout->setChild('content','hub.fulfillment.vendor','hub_fulfillment');
            $this->_view->renderLayout();
        } catch (\Throwable $e) {
            $this->getMessageManager()->addErrorMessage(__('Fulfillment is not configured or your account cannot manage it.'));
            return $this->_redirect('dashboard');
        }
    }
}
