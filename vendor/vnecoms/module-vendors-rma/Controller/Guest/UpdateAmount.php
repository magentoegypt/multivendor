<?php
/**
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsRMA\Controller\Guest;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Action\Context;
use Vnecoms\VendorsRMA\Model\Source\Email\Type as EMAIL_TYPE;
use Vnecoms\VendorsRMA\Model\Source\Message\Type as MESSAGE_TYPE;

class UpdateAmount extends \Magento\Framework\App\Action\Action
{
    /**
     * @var  \Vnecoms\VendorsRMA\Model\Request\EscalateFactory
     */
    protected $escalateFactory;

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

    protected $_helperRequest;

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
        \Vnecoms\VendorsRMA\Model\RequestFactory $requestFactory,
        \Vnecoms\VendorsRMA\Model\Request\EscalateFactory $escalateFactory,
        \Vnecoms\RMA\Helper\Config $helperTicket,
        \Vnecoms\RMA\Controller\Customer\RmaViewAuthorizationInterface $rmaAuthorization
    )
    {
        $this->escalateFactory = $escalateFactory;
        $this->rmaAuthorization = $rmaAuthorization;
        $this->requestFactory = $requestFactory;
        $this->_helperRequest = $helperTicket;
        parent::__construct($context);
    }

    /**
     * Save action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {

        $id = $this->getRequest()->getPostValue("id");
        $amount = $this->getRequest()->getPostValue("amount");

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($id) {
            $request  = $this->requestFactory->create();;
            $request->load($id);

            if (!$request->getId() || !$this->rmaAuthorization->canView($request)
                || $request->getType() != "refund"
            ) {
                $result = [
                    'error' => true,
                    'msg' =>__('This RMA no longer exists.')
                ];
                $this->getResponse()->setBody(json_encode($result));
                return;
            }
            $maxAmount = $this->_getMaxAmountRefund($request);
            if($amount > $maxAmount){
                $result = [
                    'error' => true,
                    'msg' =>__('Please enter a valid amount in this field. Max amount:'.$maxAmount)
                ];
                $this->getResponse()->setBody(json_encode($result));
                return;
            }
            try {
                $request->save();
                $request->saveAmountRefundObject("custom_amount",$amount);
                $request->sendMailAmountRefundChangeNotify();
                $block = $this->_view->getLayout()
                    ->createBlock('Vnecoms\VendorsRMA\Block\Frontend\View\Amount')
                    ->setTemplate('Vnecoms_VendorsRMA::request/amount/list.phtml')->setRma($request);

                if($request->getVendorObject()->getId()){
                    $this->_eventManager->dispatch(
                        'vnecoms_vendors_push_notification',
                        [
                            'vendor_id' => $request->getVendorObject()->getId(),
                            'type' => 'rma',
                            'message' => __('RMA #%1 has been changed amount refund'
                                ,'<strong>'.$request->getIncrementId().'</strong>'
                            ),
                            'additional_info' => ['id' => $request->getId()],
                        ]
                    );
                }

                $result = [
                    'error' => false,
                    'amount_list' => $block->toHtml(),
                    'amount_html' => $this->_formatPrice($amount , $request),
                    'amount' => $amount
                ];

            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $result = [
                    'error' => true,
                    'msg' => $e->getMessage()
                ];
            } catch (\RuntimeException $e) {
                $result = [
                    'error' => true,
                    'msg' => $e->getMessage()
                ];
            } catch (\Exception $e) {
                $result = [
                    'error' => true,
                    'msg' => $e->getMessage()
                ];
            }
        }
        $this->getResponse()->setBody(json_encode($result));
    }

    /**
     * format price refund by order object
     * @param $amount
     * @return mixed
     */
    protected function _formatPrice($amount,$request){
        return $request->getOrderObject()->formatPrice($amount);
    }


    /**
     * get Max amount
     * @return float|int
     */
    protected function _getMaxAmountRefund($request) {
        $amount = 0 ;
        foreach($request->getAllItemFromRequest() as $item) {
            $orderItem = \Magento\Framework\App\ObjectManager::getInstance()->get(
                'Magento\Sales\Model\Order\Item')->load($item->getOrderItemId());
            $amount += (($orderItem->getRowTotalInclTax() - $orderItem->getDiscountAmount())
                    /$orderItem->getQtyOrdered())*$item->getQty();
        }
        return $amount;
    }
}