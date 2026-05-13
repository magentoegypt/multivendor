<?php
/**
 * Created by PhpStorm.
 * User: nvhai
 * Date: 12/23/2016
 * Time: 11:13 AM
 */
namespace Vnecoms\VendorsShippingTableRate\Controller\Vendors\Tablerate;

use Vnecoms\Vendors\App\Action\Context;
use Magento\Cms\Controller\Adminhtml\Page\PostDataProcessor;
use Magento\Framework\App\Request\DataPersistorInterface;

class Edit extends \Vnecoms\Vendors\Controller\Vendors\Action
{


    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var PostDataProcessor
     */
    protected $dataProcessor;

    /**
     * @var RateViewAuthorizationInterface
     */
    protected $rateAuthorization;
    /**
     * Save constructor.
     * @param Context $context
     * @param PostDataProcessor $dataProcessor
     */
    public function __construct(
        Context $context,
        PostDataProcessor $dataProcessor,
        RateViewAuthorization $rateViewAuthorization,
        DataPersistorInterface $dataPersistor
    ){
        $this->dataProcessor = $dataProcessor;
        $this->rateAuthorization         = $rateViewAuthorization;
        $this->dataPersistor = $dataPersistor;
        parent::__construct($context);
    }
    /**
     * @return void
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('rate_id');
        $model = $this->_objectManager->create('Vnecoms\VendorsShippingTableRate\Model\Tablerate');

        if ($id) {
            $model->load($id);
            if (!$model->getId() || !$this->rateAuthorization->canView($model)) {
                $this->messageManager->addError(__('This Rate no longer exists.'));
                $this->_redirect('*/*');
                return;
            }
        }

        $this->_coreRegistry->register('current_rate', $model);

        $this->_initAction();
        $title = $this->_view->getPage()->getConfig()->getTitle();
        $title->prepend(__("Shipping Table Rates"));
        $this->setActiveMenu('Vnecoms_VendorsShippingTableRate::shipping_table');
        $breadcrumb = $id ? __('Edit Rate') : __('New Rate');
        $this->_addBreadcrumb($breadcrumb, $breadcrumb);
        $this->_view->renderLayout();
    }
}