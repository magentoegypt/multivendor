<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsRMA\Controller\Customer;

use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Cms\Controller\Adminhtml\Page\PostDataProcessor;
use Vnecoms\RMA\Controller\Customer\RmaViewAuthorizationInterface as RmaViewAuthorizationInterface;

class Confirm extends \Magento\Customer\Controller\AbstractAccount
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;
    /**
     * @var \Vnecoms\RMA\Model\RequestFactory
     */
    protected $requestFactory;

    /**
     * @var \Vnecoms\RMA\Model\StatusFactory
     */
    protected $status;

    /**
     * @var PostDataProcessor
     */
    protected $dataProcessor;

    /**
     * @var \Vnecoms\RMA\Helper\Config
     */

    protected $_helperConfig;
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_session;

    /**
     * @var RmaViewAuthorizationInterface
     */
    protected $rmaAuthorization;


    /**
     * Cancel constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param PostDataProcessor $dataProcessor
     * @param \Vnecoms\RMA\Model\RequestFactory $requestFactory
     * @param \Vnecoms\RMA\Model\Status $status
     * @param \Vnecoms\RMA\Helper\Config $helperConfig
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        PostDataProcessor $dataProcessor ,
        \Vnecoms\RMA\Model\RequestFactory $requestFactory,
        \Vnecoms\RMA\Model\StatusFactory $status,
        \Vnecoms\RMA\Helper\Config $helperConfig,
        RmaViewAuthorizationInterface $rmaAuthorization
    ) {
        $this->rmaAuthorization = $rmaAuthorization;
        $this->resultPageFactory    = $resultPageFactory;
        $this->dataProcessor        = $dataProcessor;
        $this->requestFactory       = $requestFactory;
        $this->status               = $status;
        $this->_helperConfig        = $helperConfig;
        parent::__construct($context);
    }

    /**
     * Customer order history
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $status = $this->status->create()->load(\Vnecoms\RMA\Model\Request::STATUS_PACKSENT,"code");
        if ($id) {
            $request  = $this->requestFactory->create();
            $request->load($id);

            if (!$request->getId() || !$this->rmaAuthorization->canView($request)) {
                $this->messageManager->addError(__('This RMA no longer exists.'));
                $this->_redirect('*/*');
                return;
            }
            $this->_eventManager->dispatch(
                'rma_request_prepare_save',
                ['rma' => $request, 'request' => $this->getRequest()]
            );
            try {
                $request->setStatus($status->getId());
                $request->save();
                $request->saveStatusHistoryObject(false);

                if($request->getVendorObject()->getId()){
                    $this->_eventManager->dispatch(
                        'vnecoms_vendors_push_notification',
                        [
                            'vendor_id' => $request->getVendorObject()->getId(),
                            'type' => 'rma',
                            'message' => __('RMA #%1 has been changed it\'s status to %2'
                                ,'<strong>'.$request->getIncrementId().'</strong>',
                                $request->getStatusTitle()
                            ),
                            'additional_info' => ['id' => $request->getId()],
                        ]
                    );
                }

                $this->messageManager->addSuccess(__('Rma was successfully changed status to package sent'));
                return $resultRedirect->setPath('*/*/');
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\RuntimeException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }

        }
        return $resultRedirect->setPath('*/*/');
    }

}
