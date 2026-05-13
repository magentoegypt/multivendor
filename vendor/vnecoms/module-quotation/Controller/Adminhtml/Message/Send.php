<?php


namespace Vnecoms\Quotation\Controller\Adminhtml\Message;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;
use Vnecoms\Quotation\Model\Message\Attachment;

class Send extends \Magento\Backend\App\Action implements HttpPostActionInterface
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Vnecoms\Quotation\Model\MessageFactory
     */
    protected $messageFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $localeDate;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $authSession;

    /**
     * @var \Vnecoms\Quotation\Model\Email
     */
    protected $mailer;

    /**
     * @var \Vnecoms\Quotation\Model\QuoteRepository
     */
    protected $quoteRepository;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $jsonFactory
     * @param \Magento\Framework\View\Result\PageFactory $pageFactory
     * @param \Vnecoms\Quotation\Model\MessageFactory $messageFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Vnecoms\Quotation\Model\Email $mailer
     * @param \Vnecoms\Quotation\Model\QuoteRepository $quoteRepository
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Vnecoms\Quotation\Model\MessageFactory $messageFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Vnecoms\Quotation\Model\Email $mailer,
        \Vnecoms\Quotation\Model\QuoteRepository $quoteRepository
    ) {
        $this->resultJsonFactory = $jsonFactory;
        $this->resultPageFactory = $pageFactory;
        $this->messageFactory = $messageFactory;
        $this->localeDate = $localeDate;
        $this->urlBuilder = $urlBuilder;
        $this->authSession = $authSession;
        $this->mailer = $mailer;
        $this->quoteRepository = $quoteRepository;
        parent::__construct($context);
    }

    /**
     * @return $this|\Magento\Framework\Controller\Result\Json
     * @throws LocalizedException
     */
    public function execute()
    {
        $request = $this->getRequest();

        try {
            $message = trim(strip_tags($request->getParam('message')));
            $quoteId = $request->getParam('quote_id');
            if (!$message || !$quoteId) {
                throw new LocalizedException(__('The post data is not valid'));
            }
            $quote = $this->quoteRepository->getById($quoteId);
            $response = [];

            $attachments = $request->getParam('attachments');
            if($attachments){
                $attachments = explode("||", $attachments);
            }

            $model = $this->messageFactory->create()
                ->setData([
                    'quote_id' => $quoteId,
                    'message' => $message,
                    'name' => $this->authSession->getUser()->getName(),
                    'user_type' => \Vnecoms\Quotation\Model\Message::TYPE_ADMIN,
                    'attachments' => $attachments
                ])->save();

            /* Send notification email*/
            $this->mailer->sendMessageEmailToCustomer($model, $quote);

            $messageData = $model->getData();

            $messageData['createdAtDate'] = $this->localeDate->formatDateTime(
                $model->getCreatedAt(),
                \IntlDateFormatter::LONG,
                \IntlDateFormatter::NONE
            );
            $messageData['createdAtTime'] = $this->localeDate->formatDateTime(
                $model->getCreatedAt(),
                \IntlDateFormatter::NONE,
                \IntlDateFormatter::SHORT
            );
            if($attachments){
                $attachments = [];
                foreach($model->getAttachmentCollection() as $attachment){
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
            }

            $response['data'] = $messageData;
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $response = ['error' => true, 'message' => $e->getMessage()];
        } catch (\Exception $e) {
            $response = ['error' => true, 'message' => __('Can not add the message.')];
        }

        $resultJson = $this->resultJsonFactory->create();
        $resultJson->setData($response);
        return $resultJson;
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
        return $this->urlBuilder->getBaseUrl([
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
}
