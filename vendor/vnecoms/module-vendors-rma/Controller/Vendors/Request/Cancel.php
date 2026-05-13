<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsRMA\Controller\Vendors\Request;

use Vnecoms\Vendors\Controller\Vendors\Action;
use Vnecoms\Vendors\App\Action\Context;
use Magento\Cms\Controller\Adminhtml\Page\PostDataProcessor;
use Magento\Framework\App\Request\DataPersistorInterface;
use Vnecoms\RMA\Model\Source\Email\Type as EMAIL_TYPE;
use Vnecoms\RMA\Model\Source\Message\Type as MESSAGE_TYPE;

class Cancel extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_Vendors::rma_request';
    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;
    /**
     * @var \Vnecoms\RMA\Model\RequestFactory
     */
    protected $requestFactory;

    /**
     * @var PostDataProcessor
     */
    protected $dataProcessor;

    /**
     * @var \Vnecoms\RMA\Helper\Config
     */

    protected $_helperConfig;

    /**
     * @var \Vnecoms\RMA\Model\StatusFactory
     */
    protected $status;

    /**
     * @var RmaViewAuthorizationInterface
     */
    protected $rmaAuthorization;

    /**
     * @param Context $context
     * @param PostDataProcessor $dataProcessor
     */
    public function __construct(
        Context $context,
        PostDataProcessor $dataProcessor ,
        \Vnecoms\RMA\Model\RequestFactory $requestFactory,
        \Vnecoms\RMA\Model\StatusFactory $status,
        \Vnecoms\RMA\Helper\Config $helperConfig,
        RmaViewAuthorizationInterface $rmaAuthorization,
        DataPersistorInterface $dataPersistor
    )
    {
        $this->rmaAuthorization = $rmaAuthorization;
        $this->dataProcessor = $dataProcessor;
        $this->dataPersistor = $dataPersistor;
        $this->status        = $status;
        $this->requestFactory = $requestFactory;
        $this->_helperConfig = $helperConfig;
        parent::__construct($context);
    }

    /**
     * Customer order history
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('request_id');

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $status = $this->status->create()->load(\Vnecoms\RMA\Model\Request::STATUS_CANCELED,"code");
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
                $request->saveStatusHistoryObject("vendor");
                $this->messageManager->addSuccess(__('Rma was successfully canceled'));
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