<?php
/**
 * Copyright © 2017 Vnecoms. All rights reserved.
 * See LICENSE.txt for license details.
 */


namespace Vnecoms\Quotation\Controller\Customer;

use Magento\Framework\Exception\NoSuchEntityException;
use Vnecoms\Quotation\Model\QuoteRepository;

use Magento\Framework\App\Action\Context;

class MessagePost extends \Magento\Customer\Controller\AbstractAccount
{
    /**
     * @var QuoteRepository
     */
    protected $quoteRepository;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;
    
    /**
     * @param Context $context
     * @param QuoteRepository $quoteRepository
     * @param \Magento\Customer\Model\Session $customerSession
     */
    public function __construct(
        Context $context,
        QuoteRepository $quoteRepository,
        \Magento\Customer\Model\Session $customerSession
    ) {
        parent::__construct($context);
        $this->quoteRepository = $quoteRepository;
        $this->customerSession = $customerSession;
    }

    public function execute()
    {
        try{
            $quoteId = $this->getRequest()->getParam('quote_id',false);
            $quote = $this->quoteRepository->getById($quoteId);
            if(
                ($quote->getCustomerId() != $this->customerSession->getCustomerId())
            ) {
                throw new NoSuchEntityException(__("The quote is not available."));
            }
            
            $message = $this->_objectManager->create('Vnecoms\Quotation\Model\Message');
            $data = [
                'message' => $this->getRequest()->getParam('message'),
                'name' => $this->customerSession->getCustomer()->getName(),
                'user_type' => \Vnecoms\Quotation\Model\Message::TYPE_CUSTOMER,
            ];
            $fileUpload = $this->getRequest()->getParam('file_upload', false);
            if($fileUpload && is_array($fileUpload)){
                $data['attachments'] = $fileUpload;
            }
            
            $message->setData($data)->setQuote($quote)->save();
            $this->messageManager->addSuccess(__('Your message is sent'));
            $this->_redirect('quotation/customer/view', ['quote_id' => $quote->getId()]);
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addError(__("The quote is not available."));
            return $this->_redirect('quotation/customer');
        }catch(\Exception $e){
            $this->messageManager->addError($e->getMessage());
            return $this->_redirect('quotation/customer');
        }
    }
}