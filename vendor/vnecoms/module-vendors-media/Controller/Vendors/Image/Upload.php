<?php

namespace Vnecoms\VendorsMedia\Controller\Vendors\Image;

use Magento\Framework\App\Filesystem\DirectoryList;

class Upload extends \Vnecoms\Vendors\Controller\Vendors\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_VendorsMedia::media';
    
    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected $resultRawFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_localeDate;
    /**
     * @param \Vnecoms\Vendors\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     */
    public function __construct(
        \Vnecoms\Vendors\App\Action\Context $context,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
    ) {
        parent::__construct($context);
        $this->_localeDate = $localeDate;
        $this->resultRawFactory = $resultRawFactory;
    }
    
    /**
     * @return void
     */
    public function execute()
    {
        try {
            $uploader = $this->_objectManager->create(
                'Magento\MediaStorage\Model\File\Uploader',
                ['fileId' => 'image_uploader']
            );
            $helper= $this->_objectManager->create('Vnecoms\VendorsMedia\Helper\Data');
            $uploader->setAllowedExtensions($helper->getAllowedExtensions());
            /** @var \Magento\Framework\Image\Adapter\AdapterInterface $imageAdapter */
            $imageAdapter = $this->_objectManager->get('Magento\Framework\Image\AdapterFactory')->create();
            $uploader->setAllowRenameFiles(false);
            $uploader->setFilesDispersion(false);
            /** @var \Magento\Framework\Filesystem\Directory\Read $mediaDirectory */
            $mediaDirectory = $this->_objectManager->get('Magento\Framework\Filesystem')
                ->getDirectoryRead(DirectoryList::MEDIA);
            
            $path = $helper->getMediaFolder($this->_session->getVendor());
            
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
            if(isset($uploader)){
                $file = $uploader->validateFile();
                $fileName = isset($file['name'])?$file['name']:'';
            }else{
                $fileName = $_FILES['image_uploader']['name'];
            }
            $result = ['name'=> $fileName,'error' => $e->getMessage(), 'errorcode' => $e->getCode()];
        }

        /** @var \Magento\Framework\Controller\Result\Raw $response */
        $response = $this->resultRawFactory->create();
        $response->setHeader('Content-type', 'text/plain');
        $response->setContents(json_encode($result));
        return $response;
    }
}
