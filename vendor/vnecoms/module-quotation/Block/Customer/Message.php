<?php

namespace Vnecoms\Quotation\Block\Customer;

use Magento\Framework\View\Element\Template;
use Vnecoms\Quotation\Model\Message\Attachment;

class Message extends \Magento\Framework\View\Element\Template
{
    protected $_template = 'customer/quote/message.phtml';

    /**
     * @var \Vnecoms\Quotation\Model\ResourceModel\Message\CollectionFactory
     */
    protected $collectionFactory;
    
    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    protected $helper;
    
    /**
     * @param Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Vnecoms\Quotation\Model\ResourceModel\Message\CollectionFactory $collectionFactory
     * @param array $data
     */
    public function __construct
    (
        Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Vnecoms\Quotation\Model\ResourceModel\Message\CollectionFactory $collectionFactory,
        \Vnecoms\Quotation\Helper\Data $helper,
        array $data = []
    ) {
        $this->coreRegistry = $registry;
        $this->collectionFactory = $collectionFactory;
        $this->helper = $helper;
        parent::__construct($context, $data);
    }

    /**
     * @return \Vnecoms\Quotation\Model\Quote
     */
    public function getQuote()
    {
        return $this->coreRegistry->registry('current_quote');
    }

    /**
     * @return string
     */
    public function isAllowCustomer()
    {
        return (bool) $this->helper->allowCustomerUpload();
    }

    /**
     * @return int
     */
    public function getAttachmentFileSizeAllow()
    {
        return $this->helper->getMaxSize();
    }

    /**
     * @return int
     */
    public function getMaxFileNumber()
    {
        return $this->helper->getMaxNumber();
    }
    
    /**
     * Get message collection
     * 
     * @return \Vnecoms\Quotation\Model\ResourceModel\Message\Collection
     */
    public function getMessages(){
        if(!$this->getData('quotation_messages')){
            $collection = $this->collectionFactory->create()
                ->setQuoteFilter($this->getQuote())
                ->setOrder('message_id','DESC');
            $this->setData('quotation_messages', $collection);
        }
        
        return $this->getData('quotation_messages');
    }
    
    /**
     * Get message css class
     * 
     * @param \Vnecoms\Quotation\Model\Message $message
     * @return string
     */
    public function getMessageCssClass(\Vnecoms\Quotation\Model\Message $message){
        switch($message->getUserType()){
            case '0': 
                return 'quote-message-customer';
            case '1':
                return 'quote-message-admin';
            case '2':
                return 'quote-message-vendor';
            default: return 'quote-message-customer';
        }
    }
    
    /**
     * Get Post message URL
     * 
     * @return string
     */
    public function getPostMessageUrl(){
        return $this->getUrl('quotation/customer/messagePost',['quote_id' => $this->getQuote()->getId()]);
    }

    /**
     * Get Ajax Post message URL
     *
     * @return string
     */
    public function getPostAjaxMessageUrl(){
        return $this->getUrl('quotation/customer/messageAjaxPost');
    }

    /**
     * Get attachment file name
     * 
     * @param Attachment $attachment
     * @return string
     */
    public function getAttachmentName(Attachment $attachment){
        $name = $attachment->getFileName();
        $name = explode('/', $name);
        $name = end($name);
        return $name;
    }

    /**
     * Get attachment file extension
     *
     * @param Attachment $attachment
     * @return string
     */
    public function getAttachmentMediaType(Attachment $attachment){
        $name = $attachment->getFileName();
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        return $ext;
    }

    /**
     * Get attachment file extension
     *
     * @param Attachment $attachment
     * @return string
     */
    public function getAttachmentMediaTypeClass(Attachment $attachment) {
        $ext = $this->getAttachmentMediaType($attachment);
        switch($ext){
            case 'rar':
    		case 'tgz':
    		case 'bz':
            case 'zip':
                return 'quote-icon-file-zip';
            case 'pdf':
                return 'quote-icon-file-pdf';
            case 'doc':
            case 'docx':
                return 'quote-icon-file-word';
            case 'xls':
            case 'xlsx':
                return 'quote-icon-file-excel';
            case 'png':
            case 'jpeg':
            case 'jpg':
            case 'gif':
                return 'quote-icon-file-image';
            default: return 'quote-icon-file-empty';
        }
    }

    /**
     * @param Attachment $attachment
     * @return bool
     */
    public function isMediaTypeImage(Attachment $attachment)
    {
        $file = $attachment->getFileName();
        $extension = pathinfo(strtolower($file), PATHINFO_EXTENSION);
        if (in_array($extension,['png','jpg','jpeg','gif'])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get attachment URL
     * 
     * @param Attachment $attachment
     * @return string
     */
    public function getAttachmentUrl(Attachment $attachment){
        return $this->_urlBuilder->getBaseUrl([
            '_type' => \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        ]).'vnecoms_quotation/'.trim($attachment->getFileName(), '/');
    }
}
