<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\Quotation\Block\Adminhtml\Quote\Edit;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Vnecoms\Quotation\Model\Message\Attachment;

/**
 * Class Comment
 * @package Vnecoms\Quotation\Block\Adminhtml\Quote\Edit
 */
class Comment extends AbstractQuote
{
    /**
     * @var
     */
    protected $messages;

    /**
     * Data Form object
     *
     * @var \Magento\Framework\Data\Form
     */
    protected $_form;

    /**
     * @var array
     */
    protected $jsLayout;

    /**
     * @var \Vnecoms\Quotation\Helper\Data
     */
    protected $helper;


    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Vnecoms\Quotation\Model\Backend\Session $sessionQuote
     * @param \Vnecoms\Quotation\Model\Quote $quoteCreate
     * @param PriceCurrencyInterface $priceCurrency
     * @param \Magento\Framework\Registry $registry
     * @param \Vnecoms\Quotation\Helper\Data $helper
     * @param array $data
     */
    public function __construct
    (
        \Magento\Backend\Block\Template\Context $context,
        \Vnecoms\Quotation\Model\Backend\Session $sessionQuote,
        \Vnecoms\Quotation\Model\Quote $quoteCreate,
        PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\Registry $registry,
        \Vnecoms\Quotation\Helper\Data $helper,
        array $data = []
    )
    {
        $this->jsLayout = isset($data['jsLayout']) && is_array($data['jsLayout']) ? $data['jsLayout'] : [];
        $this->helper = $helper;
        parent::__construct($context, $sessionQuote, $quoteCreate, $priceCurrency, $registry, $data);
    }

    public function getJsLayout()
    {
        $this->jsLayout['components']['quote_messages']['component'] = 'Vnecoms_Quotation/js/messages';
        $this->jsLayout['components']['quote_messages']['template'] = 'Vnecoms_Quotation/messages';
        $this->jsLayout['components']['quote_messages']['messages'] = $this->getMessages();
        $this->jsLayout['components']['quote_messages']['quote_id'] = $this->getRealQuote()->getId();
        $this->jsLayout['components']['quote_messages']['allowCustomerUpload'] = $this->helper->allowCustomerUpload();
        $this->jsLayout['components']['quote_messages']['uploadUrl'] = $this->getUrl('quotation/message/attach');
        $this->jsLayout['components']['quote_messages']['addMessageUrl'] = $this->getUrl('quotation/message/send');
        $this->jsLayout['components']['quote_messages']['children'] = [
            [
                'component' => 'Vnecoms_Quotation/js/uploader',
                'template' => 'Vnecoms_Quotation/uploader/uploader',
                'previewTmpl' => 'Vnecoms_Quotation/uploader/preview',
                'displayArea' => 'uploader',
                'allowedExtensions' => explode(',',$this->helper->getAllowExtensions()),
                'uploaderConfig' => [
                    'url' => $this->getUrl('quotation/attachment/upload'),
                    'acceptFileTypes' => explode(',',$this->helper->getAllowExtensions()),
                    'maxFileSize' => $this->helper->getMaxSize(), //by bytes
                    'maxFileNumber' => $this->helper->getMaxNumber() //default 5 files
                ]
            ],
        ];
        return \Laminas\Json\Json::encode($this->jsLayout);
    }

    /**
     * Get header css class
     *
     * @return string
     */
    public function getHeaderCssClass()
    {
        return 'head-comment';
    }

    /**
     * Get header text
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        return __('Messages History');
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

    /**
     * @param Attachment $attachment
     * @return bool
     */
    public function isMediaTypeImage(Attachment $attachment)
    {
        $file = $attachment->getFileName();
        $extension = pathinfo(strtolower($file), PATHINFO_EXTENSION);
        return in_array($extension,['png','jpg','jpeg','gif']);
    }

    /**
     * Get attachment file extension
     *
     * @param Attachment $attachment
     * @return string
     */
    public function getAttachmentMediaTypeClass(Attachment $attachment) {
        $ext = pathinfo(strtolower($attachment->getFileName()), PATHINFO_EXTENSION);
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
     * @return string
     */
    public function getMessages()
    {
        $result = [];
        $messageCollection = $this->getRealQuote()->getMessagesCollection()->addOrder('message_id', 'desc');
        foreach($messageCollection as $message){
            $messageData = $message->getData();
            $messageData['createdAtDate'] = $this->formatDate($message->getCreatedAt(), \IntlDateFormatter::LONG);
            $messageData['createdAtTime'] = $this->formatTime($message->getCreatedAt());
            $attachments = [];
            foreach($message->getAttachmentCollection() as $attachment){
                $attachments[] = [
                    'id' => $attachment->getId(),
                    'name' => $this->getAttachmentName($attachment),
                    'url' => $this->getAttachmentUrl($attachment),
                    'is_image' => $this->isMediaTypeImage($attachment),
                    'icon' => $this->getAttachmentMediaTypeClass($attachment),
                    'file' => $attachment->getFileName(),
                    'download_url' => $this->getUrl(
                        'quotation/attachment/download',
                        ['file' => base64_encode($attachment->getFileName())]
                    ),
                ];
            }
            $messageData['attachments'] = $attachments;
            $messageData['attachments_count'] = sizeof($attachments);
            $result[] = $messageData;
        }

        return $result;
    }
}
