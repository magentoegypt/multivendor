<?php

namespace Vnecoms\Quotation\Controller\Attachment;

use Magento\Framework\App\Filesystem\DirectoryList;
use Vnecoms\Quotation\Helper\Guest;
use Magento\Framework\Exception\NoSuchEntityException;

class Upload extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected $resultRawFactory;
    
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_localeDate;

    /**
     * @var \Vnecoms\Quotation\Helper\Data
     */
    protected $quotationHelper;
    
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;
    
    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Vnecoms\Quotation\Helper\Data $quotationHelper
     * @param \Magento\Customer\Model\Session $customerSession
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Vnecoms\Quotation\Helper\Data $quotationHelper,
        \Magento\Customer\Model\Session $customerSession
    ) {
        parent::__construct($context);
        $this->quotationHelper = $quotationHelper;
        $this->_localeDate = $localeDate;
        $this->resultRawFactory = $resultRawFactory;
        $this->customerSession = $customerSession;
    }
    
    /**
     * @return void
     */
    public function execute()
    {
        try {
            if(
                !$this->customerSession->isLoggedIn() &&
                !$this->customerSession->getData(Guest::QUOTATION_GUEST_KEY)
            ) {
                throw new NoSuchEntityException(__("You are not authorized to upload file."));
            }
            $uploader = $this->_objectManager->create(
                'Magento\MediaStorage\Model\File\Uploader',
                ['fileId' => 'image']
            );
            $allowedExtensions = $this->quotationHelper->getConfig('quotation/message/allow_extensions');
            $extensions = explode(',', $allowedExtensions);
            if (is_array($extensions) && count($extensions) > 0) $uploader->setAllowedExtensions($extensions);
            
            /** @var \Magento\Framework\Image\Adapter\AdapterInterface $imageAdapter */
            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(true);
            
            /** @var \Magento\Framework\Filesystem\Directory\Read $mediaDirectory */
            $mediaDirectory = $this->_objectManager->get('Magento\Framework\Filesystem')
                ->getDirectoryRead(DirectoryList::MEDIA);
    
            $path = 'vnecoms_quotation';
    
            $result = $uploader->save($mediaDirectory->getAbsolutePath(
                $path
            ));

            $storeManager = $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface');
            $result['url'] = $storeManager->getStore()
                ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . $path.'/' . $result['file'];
            
            $result['last_modify'] = $this->_localeDate->formatDate(
                date("Y-m-d H:i:s", filemtime($result['path'])),
                \IntlDateFormatter::SHORT,
                true
            );
            unset($result['tmp_name']);
            unset($result['path']);
        } catch (\Exception $e) {
            $result = ['error' => $e->getMessage(), 'errorcode' => $e->getCode()];
        }
    
        /** @var \Magento\Framework\Controller\Result\Raw $response */
        $response = $this->resultRawFactory->create();
        $response->setHeader('Content-type', 'text/plain');
        $response->setContents(json_encode($result));
        return $response;
    }
}
