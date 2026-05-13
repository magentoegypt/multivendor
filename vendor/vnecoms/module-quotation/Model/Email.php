<?php

namespace Vnecoms\Quotation\Model;

use Vnecoms\Quotation\Helper\Data as Helper;
use Vnecoms\Quotation\Helper\Email as EmailHelper;
use Magento\Store\Model\ScopeInterface;

class Email
{
    /**
     * @var \Vnecoms\Quotation\Helper\Email
     */
    protected $emailHelper;
    
    /**
     * @var \Vnecoms\Quotation\Helper\Data
     */
    protected $helper;
    
    /**
     * @var \Magento\Framework\Url
     */
    protected $urlBuilder;
    
    /**
     * @param EmailHelper $emailHelper
     * @param Helper $helper
     * @param \Magento\Framework\UrlInterface $urlBuilder
     */
    public function __construct(
        EmailHelper $emailHelper,
        Helper $helper,
        \Magento\Framework\Url $urlBuilder
    ) {
        $this->emailHelper = $emailHelper;
        $this->helper = $helper;
        $this->urlBuilder = $urlBuilder;
    }
    
    
    /**
     * Get Quote Params
     *
     * @param \Vnecoms\Quotation\Model\Quote $quote
     * @return multitype:unknown \Vnecoms\Quotation\Model\Quote NULL multitype:Ambigous <number, string> Ambigous <number, string, \Magento\Framework\App\Config\mixed>
     */
    public function getQuoteParams(\Vnecoms\Quotation\Model\Quote $quote){
        if($quote->getCustomerId()){
            $viewQuoteUrl = $this->urlBuilder->getUrl('quotation/customer/view',['quote_id' => $quote->getId()]);
        }else{
            $viewQuoteUrl = $this->urlBuilder->getUrl(
                'quotation/guest/view',
                [
                    'quote_id' => base64_encode($quote->getIncrementId()),
                    'customer_lastname' => $quote->getLastName(),
                    'type' => 'customer_email',
                    'customer_email' => $quote->getCustomerEmail(),
                ]
            );
        }
		
        return [
            'quote' => $quote,
            'store' => $quote->getStore(),
            'view_quote_url' => $viewQuoteUrl,
            'config' => [
                'show_telephone' => $this->helper->getTelephoneConfig($quote->getStoreId()),
                'show_company' => $this->helper->getCompanyConfig($quote->getStoreId()),
                'show_taxvat' => $this->helper->getTaxIdConfig($quote->getStoreId()),
                'customer_name' => $quote->getCustomerName()
            ],
        ];
    }
    
    /**
     * Send quote notification email
     *
     * @param string|array $mailTo
     * @param string $emailTemplate
     * @param array $params
     * @param array $attachments
     */
    public function sendMail(
        $mailTo,
        $emailTemplate,
        $storeId = 0,
        $params = [],
        $attachments = []
    ) {
        /*Send email*/
        $this->emailHelper->sendTransactionEmail(
            $emailTemplate,
            \Magento\Framework\App\Area::AREA_FRONTEND,
            Helper::XML_PATH_EMAIL_IDENTITY,
            $mailTo,
            $params,
            $attachments,
            '',
            $storeId,
            ScopeInterface::SCOPE_STORE
        );
    }
    
    /**
     * Send new quote notification email to customer
     * 
     * @param \Vnecoms\Quotation\Model\Quote $quote
     */
    public function sendNewQuoteEmailToCustomer(\Vnecoms\Quotation\Model\Quote $quote){
        $emailTemplate = $quote->getCustomerId()?
            Helper::XML_PATH_EMAIL_CUSTOMER_NEW_QUOTE:
            Helper::XML_PATH_EMAIL_CUSTOMER_NEW_QUOTE_GUEST;
        
        $this->sendMail(
            $quote->getCustomerEmail(),
            $emailTemplate,
            $quote->getStoreId(),
            $this->getQuoteParams($quote)
        );
    }
    
    /**
     * Send approved quote notification email to customer
     *
     * @param \Vnecoms\Quotation\Model\Quote $quote
     */
    public function sendApprovedQuoteEmailToCustomer(\Vnecoms\Quotation\Model\Quote $quote){
        $emailTemplate = $quote->getCustomerId()?
            Helper::XML_PATH_EMAIL_CUSTOMER_QUOTE_APPROVED:
            Helper::XML_PATH_EMAIL_CUSTOMER_QUOTE_APPROVED_GUEST;
        
        $this->sendMail(
            $quote->getCustomerEmail(),
            $emailTemplate,
            $quote->getStoreId(),
            $this->getQuoteParams($quote)
        );
    }
    
    /**
     * Send cancelled quote notification email to customer
     *
     * @param \Vnecoms\Quotation\Model\Quote $quote
     */
    public function sendCancelledQuoteEmailToCustomer(\Vnecoms\Quotation\Model\Quote $quote){
        $emailTemplate = $quote->getCustomerId()?
            Helper::XML_PATH_EMAIL_CUSTOMER_QUOTE_CANCELLED:
            Helper::XML_PATH_EMAIL_CUSTOMER_QUOTE_CANCELLED_GUEST;
        
        $this->sendMail(
            $quote->getCustomerEmail(),
            $emailTemplate,
            $quote->getStoreId(),
            $this->getQuoteParams($quote)
        );
    }
    
    /**
     * Send held quote notification email to customer
     *
     * @param \Vnecoms\Quotation\Model\Quote $quote
     */
    public function sendHeldQuoteEmailToCustomer(\Vnecoms\Quotation\Model\Quote $quote){
        $emailTemplate = $quote->getCustomerId()?
            Helper::XML_PATH_EMAIL_CUSTOMER_QUOTE_HELD:
            Helper::XML_PATH_EMAIL_CUSTOMER_QUOTE_HELD_GUEST;
        
        $this->sendMail(
            $quote->getCustomerEmail(),
            $emailTemplate,
            $quote->getStoreId(),
            $this->getQuoteParams($quote)
        );
    }
    
    /**
     * Send unheld quote notification email to customer
     *
     * @param \Vnecoms\Quotation\Model\Quote $quote
     */
    public function sendUnheldQuoteEmailToCustomer(\Vnecoms\Quotation\Model\Quote $quote){
        $emailTemplate = $quote->getCustomerId()?
            Helper::XML_PATH_EMAIL_CUSTOMER_QUOTE_UNHELD:
            Helper::XML_PATH_EMAIL_CUSTOMER_QUOTE_UNHELD_GUEST;
        
        $this->sendMail(
            $quote->getCustomerEmail(),
            $emailTemplate,
            $quote->getStoreId(),
            $this->getQuoteParams($quote)
        );
    }
    
    /**
     * Send rejected quote notification email to customer
     *
     * @param \Vnecoms\Quotation\Model\Quote $quote
     */
    public function sendRejectedQuoteEmailToCustomer(\Vnecoms\Quotation\Model\Quote $quote){
        $emailTemplate = $quote->getCustomerId()?
            Helper::XML_PATH_EMAIL_CUSTOMER_QUOTE_REJECTED:
            Helper::XML_PATH_EMAIL_CUSTOMER_QUOTE_REJECTED_GUEST;
        
        $this->sendMail(
            $quote->getCustomerEmail(),
            $emailTemplate,
            $quote->getStoreId(),
            $this->getQuoteParams($quote)
        );
    }
    
    /**
     * Send expired quote notification email to customer
     *
     * @param \Vnecoms\Quotation\Model\Quote $quote
     */
    public function sendExpiredQuoteEmailToCustomer(\Vnecoms\Quotation\Model\Quote $quote){
        $emailTemplate = $quote->getCustomerId()?
            Helper::XML_PATH_EMAIL_CUSTOMER_QUOTE_EXPIRED:
            Helper::XML_PATH_EMAIL_CUSTOMER_QUOTE_EXPIRED_GUEST;
        
        $this->sendMail(
            $quote->getCustomerEmail(),
            $emailTemplate,
            $quote->getStoreId(),
            $this->getQuoteParams($quote)
        );
    }
    
    /**
     * Send reminder quote notification email to customer
     *
     * @param \Vnecoms\Quotation\Model\Quote $quote
     */
    public function sendReminderQuoteEmailToCustomer(\Vnecoms\Quotation\Model\Quote $quote){
        $emailTemplate = Helper::XML_PATH_EMAIL_CUSTOMER_QUOTE_REMINDER;
        
        $this->sendMail(
            $quote->getCustomerEmail(),
            $emailTemplate,
            $quote->getStoreId(),
            $this->getQuoteParams($quote)
        );
    }
    
    /**
     * Send message notification email
     * 
     * @param \Vnecoms\Quotation\Model\Message $message
     * @param \Vnecoms\Quotation\Model\Quote $quote
     */
    public function sendMessageEmailToCustomer(
        \Vnecoms\Quotation\Model\Message $message,
        \Vnecoms\Quotation\Model\Quote $quote
    ) {
        $emailTemplate = Helper::XML_PATH_EMAIL_CUSTOMER_QUOTE_MESSAGE;
        $params = $this->getQuoteParams($quote);
        $params['message'] = $message;
        /*
        $attachments = [];
        foreach($message->getAttachmentCollection() as $attachment){
            $attachments[] = $attachment;
        }
        */
        
        $this->sendMail(
            $quote->getCustomerEmail(),
            $emailTemplate,
            $quote->getStoreId(),
            $params
        );
    }

    /**
     * Send new quote notification email to admin
     *
     * @param \Vnecoms\Quotation\Model\Quote $quote
     */
    public function sendNewQuoteEmailToAdmin(\Vnecoms\Quotation\Model\Quote $quote){
        $emailTemplate = Helper::XML_PATH_EMAIL_ADMIN_NEW_QUOTE;
        $this->sendMail(
            $this->helper->getAdminEmails(),
            $emailTemplate,
            $quote->getStoreId(),
            $this->getQuoteParams($quote)
        );
    }
	/**
     * Send rejected quote notification email to admin
     *
     * @param \Vnecoms\Quotation\Model\Quote $quote
     */
    public function sendRejectedQuoteEmailToAdmin(\Vnecoms\Quotation\Model\Quote $quote){
        $emailTemplate = Helper::XML_PATH_EMAIL_ADMIN_QUOTE_REJECTED;
        $this->sendMail(
            $this->helper->getAdminEmails(),
            $emailTemplate,
            $quote->getStoreId(),
            $this->getQuoteParams($quote)
        );
    }
    
    
    /**
     * Send ordered quote notification email to admin
     *
     * @param \Vnecoms\Quotation\Model\Quote $quote
     */
    public function sendOrderedQuoteEmailToAdmin(\Vnecoms\Quotation\Model\Quote $quote){
        $emailTemplate = Helper::XML_PATH_EMAIL_ADMIN_QUOTE_ORDERED;
        $this->sendMail(
            $this->helper->getAdminEmails(),
            $emailTemplate,
            $quote->getStoreId(),
            $this->getQuoteParams($quote)
        );
    }
    
    /**
     * Send message notification email to admin
     *
     * @param \Vnecoms\Quotation\Model\Quote $quote
     */
    public function sendMessageEmailToAdmin(
        \Vnecoms\Quotation\Model\Message $message,
        \Vnecoms\Quotation\Model\Quote $quote
    ) {
        $emailTemplate = Helper::XML_PATH_EMAIL_ADMIN_QUOTE_MESSAGE;
        $params = $this->getQuoteParams($quote);
        $params['message'] = $message;
        $this->sendMail(
            $this->helper->getAdminEmails(),
            $emailTemplate,
            $quote->getStoreId(),
            $params
        );
    }
    
}
