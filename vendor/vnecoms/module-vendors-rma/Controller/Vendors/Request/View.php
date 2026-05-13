<?php
/**
 * Created by PhpStorm.
 * User: nvhai
 * Date: 06/15/2016
 * Time: 02:37 PM
 */
namespace Vnecoms\VendorsRMA\Controller\Vendors\Request;


class View extends \Vnecoms\VendorsRMA\Controller\Vendors\Vendors
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_Vendors::rma_request';
    /**
     * @var RmaViewAuthorizationInterface
     */
    protected $rmaAuthorization;

    /**
     * @param \Vnecoms\Vendors\App\Action\Context $context
     * @param \Vnecoms\Vendors\App\ConfigInterface $config
     * @param Registry $coreRegistry
     * @param Date $dateFilter
     */
    public function __construct(
        \Vnecoms\Vendors\App\Action\Context $context,
        RmaViewAuthorizationInterface $rmaAuthorization
    ) {
        $this->rmaAuthorization = $rmaAuthorization;
        parent::__construct($context);
    }
    

    /**
     * @return void
     */
    public function execute()
    {
        $vendorId = $this->_session->getVendor()->getId();
        $this->_coreRegistry->register('vendor_id', $vendorId);
        $id = $this->getRequest()->getParam('request_id');
        $model = $this->_objectManager->create('Vnecoms\RMA\Model\Request');
        if ($id) {
            $model->load($id);
            if (!$model->getId() || !$this->rmaAuthorization->canView($model) ) {
                $this->messageManager->addError(__('This RMA no longer exists.'));
                $this->_redirect('*/*');
                return;
            }
        }
        // set entered data if was error when we do save
        $data = $this->_objectManager->get('Magento\Backend\Model\Session')->getTemplateData(true);
        if (!empty($data)) {
            $model->addData($data);
        }

        if($model->getEscalateObject()->getId() &&
            $model->getState() == \Vnecoms\VendorsRMA\Model\Request::STATE_AWAITING) {
            $this->messageManager->addWarning(__('The customer has escalated their RMA request to Admin, 
            please <a href="%1">click here</a> to provide more evidences.',$this->getUrl("*/*/escalate",
                array("request_id"=>$model->getId()))));
        }

        if($model->getEscalateObject(true)->getId() &&
            $model->getState() == \Vnecoms\VendorsRMA\Model\Request::STATE_AWAITING) {
            $this->messageManager->addNotice(__("The RMA request is escalated, please wait for the other party's response"));
        }

        if(!$model->getData("is_vendor_read"))
            $model->setData("is_vendor_read",1)->save();

        $this->_coreRegistry->register('current_request', $model);
        $this->_initAction();
        $this->setActiveMenu('Vnecoms_Vendors::rma_request');
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Manage RMA'));
        $this->_view->getPage()->getConfig()->getTitle()->prepend(
            $model->getId() ? "[#".$model->getIncrementId()."]".$model->getTitle() : __('New RMA')
        );

        $breadcrumb = $id ? __('View RMA') : __('New RMA');
        $this->_addBreadcrumb($breadcrumb, $breadcrumb);
        $this->_view->renderLayout();
    }


}