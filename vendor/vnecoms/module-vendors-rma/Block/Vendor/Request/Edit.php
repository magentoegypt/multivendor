<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Catalog rule edit form block
 */
namespace Vnecoms\VendorsRMA\Block\Vendor\Request;

class Edit extends \Vnecoms\Vendors\Block\Vendors\Widget\Form\Container
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        \Vnecoms\RMA\Helper\Data $requestData,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }
    protected function _construct()
    {

        $this->_objectId = 'id';
        $this->_blockGroup = 'Vnecoms_VendorsRMA';
        $this->_controller = 'vendor_request';

        parent::_construct();


        $request = $this->_coreRegistry->registry('current_request');

        $this->buttonList->add(
            'print',
            [
                'label' => __('Print'),
                'onclick'   => 'setLocation(\'' . $this->getPrintRequestUrl($request) . '\')',
            ],
            10
        );


        if($request->getState() != \Vnecoms\RMA\Model\Request::STATE_CLOSED
            && $request->getState() != \Vnecoms\RMA\Model\Request::STATE_CANCELED
            &&  $request->canEscalate(true)
        ) {
            $this->buttonList->add(
                'escalate',
                [
                    'class' => 'btn-github action-escalate',
                    'label' => __('Escalate RMA'),
                    'onclick' => 'setLocation(\'' . $this->getEscalateUrl($request) . '\')',
                ],
                10
            );
        }

        $status = $request->getStatusObject();

        if($status->getCode() == \Vnecoms\RMA\Model\Request::STATUS_PENDING){
            $this->buttonList->add(
                'approval',
                [
                    'class' => 'btn-github action-approval',
                    'label' => __('Accept Request'),
                    'onclick'   => 'setLocation(\'' . $this->getApprovalUrl($request) . '\')',
                ],
                10
            );
        }

        if($status->getCode() == \Vnecoms\RMA\Model\Request::STATUS_PACKSENT
            || $status->getCode() == \Vnecoms\RMA\Model\Request::STATUS_APPROVAL){
            $message = __('You need to make sure that you have received the product back from the customer.');
            $this->buttonList->add(
                'package_received',
                [
                    'class' => 'action-scalable action-package_received',
                    'label' => __('Confirm Package Received'),
                    'onclick' => "confirmSetLocation('{$message}', '{$this->getPackageReceivedUrl($request)}')"
                ],
                10
            );
        }
        if ($request->getType() == \Vnecoms\RMA\Model\Request::TYPE_REPLACE) {
            if($status->getCode() == \Vnecoms\RMA\Model\Request::STATUS_RECEIVED){
                $this->buttonList->add(
                    'package_closed',
                    [
                        'class' => 'btn-github action-package_returned',
                        'label' => __('Confirm Package Is Returned'),
                        'onclick'   => 'setLocation(\'' . $this->getPackageReturnedUrl($request) . '\')',
                    ],
                    10
                );
            }

            if($status->getCode() == \Vnecoms\RMA\Model\Request::STATUS_RETURNED){
                $message = __('You need to make sure all the processes are done');
                $this->buttonList->add(
                    'package_closed',
                    [
                        'class' => 'btn-github action-closed',
                        'label' => __('Mark As Resolved'),
                        'onclick' => "confirmSetLocation('{$message}', '{$this->getCloseUrl($request)}')"
                    ],
                    10
                );
            }
        }else{
            if ($status->getCode() == \Vnecoms\RMA\Model\Request::STATUS_RECEIVED) {
                $message = __('You need to make sure all the processes are done');
                $this->buttonList->add(
                    'package_closed',
                    [
                        'class' => 'btn-github action-closed',
                        'label' => __('Mark As Resolved'),
                        'onclick' => "confirmSetLocation('{$message}', '{$this->getCloseUrl($request)}')"
                    ],
                    10
                );
            }
        }

        $this->buttonList->remove("reset");
        $this->buttonList->remove("save");
    }

    /**
     * get Escalate Request
     * @return mixed
     */
    public function getEscalateUrl($request){
        return $this->getUrl('*/*/escalate',array('request_id'=>$request->getId()));
    }

    /**
     * get Url colose Request
     * @return mixed
     */
    public function getCancelUrl($request) {
        return $this->getUrl('*/*/cancel',array('request_id'=>$request->getId()));
    }


    /**
     * get UrlConfirm Package Is Returned
     * @return mixed
     */
    public function getPackageReturnedUrl($request){
        return $this->getUrl('*/*/return',array('request_id'=>$request->getId()));
    }

    /**
     * get Url colose Request
     * @return mixed
     */
    public function getApprovalUrl($request) {
        return $this->getUrl('*/*/approval',array('request_id'=>$request->getId()));
    }

    /**
     * get Url colose Request
     * @return mixed
     */
    public function getPackageReceivedUrl($request) {
        return $this->getUrl('*/*/package',array('request_id'=>$request->getId()));
    }
    /**
     * get Url colose Request
     * @return mixed
     */
    public function getCloseUrl($request) {
        return $this->getUrl('*/*/close',array('request_id'=>$request->getId()));
    }

    /**
     * get Url colose Request
     * @return mixed
     */
    public function getPrintRequestUrl($request) {
        return $this->getUrl('*/*/print',array('request_id'=>$request->getId()));
    }



    /**
     * Getter for form header text
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText() {
        $ticket = $this->_coreRegistry->registry('current_request');
        if ($ticket->getRuleId()) {
            return __("View RMA #'%1'", $this->escapeHtml($ticket->getIncrementId()));
        } else {
            return __('New RMA');
        }
    }
}
