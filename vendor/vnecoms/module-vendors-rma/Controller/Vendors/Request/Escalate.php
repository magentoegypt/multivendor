<?php
/**
 * Created by PhpStorm.
 * User: nvhai
 * Date: 06/15/2016
 * Time: 02:37 PM
 */
namespace Vnecoms\VendorsRMA\Controller\Vendors\Request;


class Escalate extends \Vnecoms\VendorsRMA\Controller\Vendors\Vendors
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
        $request = $this->_objectManager->create('Vnecoms\VendorsRMA\Model\Request');
        if ($id) {
            $request->load($id);
            if (!$request->getId() || !$this->rmaAuthorization->canView($request) || $request->getEscalateObject(true)->getId()) {
                $this->messageManager->addError(__('This RMA no longer exists.'));
                $this->_redirect('*/*');
                return;
            }
        }
        // set entered data if was error when we do save
        $data = $this->_objectManager->get('Magento\Backend\Model\Session')->getTemplateData(true);
        if (!empty($data)) {
            $request->addData($data);
        }
        $this->_coreRegistry->register('current_request', $request);
        $this->_initAction();
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Manage RMA'));
        $this->_view->getPage()->getConfig()->getTitle()->prepend(
            $request->getId() ? "[#".$request->getIncrementId()."]".$request->getTitle() : __('Escalate RMA')
        );

        $breadcrumb = $id ? __('Escalate RMA') : __('Escalate RMA');
        $this->_addBreadcrumb($breadcrumb, $breadcrumb);
        $this->_view->renderLayout();
    }


}