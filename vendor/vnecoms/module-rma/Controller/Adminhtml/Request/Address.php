<?php
/**
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Controller\Adminhtml\Request;

use Vnecoms\RMA\Controller\Adminhtml\Request\Request;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Cms\Controller\Adminhtml\Page\PostDataProcessor;

class Address extends Action
{
    /**
     * @var \Vnecoms\RMA\Model\AddressFactory
     */
    protected $addressFactory;

    /**
     * @var PostDataProcessor
     */
    protected $dataProcessor;

    /**
     * @var \Vnecoms\RMA\Helper\Config
     */

    protected $_helperRequest;

    /**
     * @param Context $context
     * @param PostDataProcessor $dataProcessor
     */
    public function __construct(
        Context $context,
        PostDataProcessor $dataProcessor,
        \Vnecoms\RMA\Model\AddressFactory $addressFactory,
        \Vnecoms\RMA\Helper\Config $helperTicket
    ) {
    
        $this->dataProcessor = $dataProcessor;
        $this->addressFactory = $addressFactory;
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
        $data = $this->getRequest()->getPostValue("address");
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect* */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($data["address_id"]) {
            $data = $this->dataProcessor->filter($data);
            $address  = $this->addressFactory->create();
            ;
            $address->load($data["address_id"]);
            if (!$address->getId()) {
                $result = [
                'error' => true,
                'msg' => __("Address do not exist!")
                ];
                $this->getResponse()->setBody(json_encode($result));
                return;
            }

            $address->setData($data);
            try {
                $address->save();
                $result = [
                'error' => false,
                'message_list' => null
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
}
