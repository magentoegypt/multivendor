<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Catalog rule edit form block
 */
namespace Vnecoms\RMA\Block\Adminhtml\Request;

class Edit extends \Magento\Backend\Block\Widget\Form\Container
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

    /**
     * Initialize form
     * Add standard buttons
     * Add "Save and Apply" button
     * Add "Save and Continue" button
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_objectId = 'mark_form';
        $this->_blockGroup = 'Vnecoms_RMA';
        $this->_controller = 'adminhtml_request';

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


        $message = __('Are you sure you want to delete the RMA?');

        $this->buttonList->add(
            'delete',
            [
                'class' => 'action-scalable action-secondary action-delete',
                'label' => __('Delete RMA'),
                'onclick' => "confirmSetLocation('{$message}', '{$this->getDeleteRequestUrl($request)}')"
            ],
            10
        );

        if ($request->getState() == \Vnecoms\RMA\Model\Request::STATE_OPEN) {
            $message = __('Are you sure you want to cancel the RMA?');

            $this->buttonList->add(
                'cancel',
                [
                    'class' => 'action-scalable action-secondary',
                    'label' => __('Cancel RMA'),
                    'onclick' => "confirmSetLocation('{$message}', '{$this->getCancelUrl($request)}')"
                ],
                10
            );
        }

        $status = $request->getStatusObject();

        if ($status->getCode() == \Vnecoms\RMA\Model\Request::STATUS_PENDING) {
            $this->buttonList->add(
                'approval',
                [
                    'class' => 'action-scalable action-approval',
                    'label' => __('Accept Request'),
                    'onclick'   => 'setLocation(\'' . $this->getApprovalUrl($request) . '\')',
                ],
                10
            );
        }

        if ($status->getCode() == \Vnecoms\RMA\Model\Request::STATUS_PACKSENT
            || $status->getCode() == \Vnecoms\RMA\Model\Request::STATUS_APPROVAL) {
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
            if ($status->getCode() == \Vnecoms\RMA\Model\Request::STATUS_RECEIVED) {
                $this->buttonList->add(
                    'package_closed',
                    [
                        'class' => 'btn-github action-package_returned',
                        'label' => __('Confirm Package Returned'),
                        'onclick' => 'setLocation(\'' . $this->getPackageReturnedUrl($request) . '\')',
                    ],
                    10
                );
            }

            if ($status->getCode() == \Vnecoms\RMA\Model\Request::STATUS_RETURNED) {
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
        } else {
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
     * get Url colose Request
     * @return mixed
     */
    public function getDeleteRequestUrl($request)
    {
        return $this->getUrl('*/*/delete', ['request_id'=>$request->getId()]);
    }

    /**
     * get Url colose Request
     * @return mixed
     */
    public function getCancelUrl($request)
    {
        return $this->getUrl('*/*/cancel', ['request_id'=>$request->getId()]);
    }

    /**
     * get UrlConfirm Package Is Returned
     * @return mixed
     */
    public function getPackageReturnedUrl($request)
    {
        return $this->getUrl('*/*/return', ['request_id'=>$request->getId()]);
    }

    /**
     * get Url colose Request
     * @return mixed
     */
    public function getApprovalUrl($request)
    {
        return $this->getUrl('*/*/approval', ['request_id'=>$request->getId()]);
    }

    /**
     * get Url colose Request
     * @return mixed
     */
    public function getPackageReceivedUrl($request)
    {
        return $this->getUrl('*/*/package', ['request_id'=>$request->getId()]);
    }
    /**
     * get Url close Request
     * @return mixed
     */
    public function getCloseUrl($request)
    {
        return $this->getUrl('*/*/close', ['request_id'=>$request->getId()]);
    }


    /**
     * get Url colose Request
     * @return mixed
     */
    public function getPrintRequestUrl($request)
    {
        return $this->getUrl('*/*/print', ['request_id'=>$request->getId()]);
    }



    /**
     * Getter for form header text
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        $ticket = $this->_coreRegistry->registry('current_request');
        if ($ticket->getRuleId()) {
            return __("View RMA #'%1'", $this->escapeHtml($ticket->getIncrementId()));
        } else {
            return __('New RMA');
        }
    }
}
