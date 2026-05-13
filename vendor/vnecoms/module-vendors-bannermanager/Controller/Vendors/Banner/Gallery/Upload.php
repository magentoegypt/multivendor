<?php

namespace Vnecoms\BannerManager\Controller\Vendors\Banner\Gallery;


use Vnecoms\Vendors\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\UrlInterface;

/**
 * Class Upload respond for get post data image upload
 *
 * @category Vnecoms
 * @package  Vnecoms_BannerManager
 * @module   BannerManager
 * @author   Vnecoms Developer Team
 */
class Upload extends \Vnecoms\Vendors\Controller\Vendors\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_BannerManager::banners';
    
    /** Location directory save image */
    const BASE_MEDIA_PATH = 'ves/bannermanager/images';

    /** @var \Magento\Framework\Image\AdapterFactory  */
    protected $_adapterFactory;

    /** @var \Magento\MediaStorage\Model\File\UploaderFactory  */
    protected $_uploader;

    /** @var \Magento\Framework\Filesystem  */
    protected $_fileSystem;

    /** @var \Magento\Catalog\Model\Product\Media\Config  */
    protected $_config;

    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected $_resultRawFactory;

    /** @var \Magento\Framework\UrlInterface  */
    protected $_urlBuilder;

    protected $vendorSession;

    /**
     * Upload constructor
     *
     * @param Context $context
     * @param \Magento\Framework\Image\AdapterFactory $adapterFactory
     * @param \Magento\MediaStorage\Model\File\UploaderFactory $uploader
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Catalog\Model\Product\Media\Config $config
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     */
    public function __construct(
        \Vnecoms\Vendors\App\Action\Context $context,
        \Magento\Framework\Image\AdapterFactory $adapterFactory,
        \Magento\MediaStorage\Model\File\UploaderFactory $uploader,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Catalog\Model\Product\Media\Config $config,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
    ) {
        parent::__construct($context);
        $this->_adapterFactory = $adapterFactory;
        $this->_uploader = $uploader;
        $this->_fileSystem = $filesystem;
        $this->_config = $config;
        $this->_resultRawFactory = $resultRawFactory;
        $this->_urlBuilder = $context->getUrl();
    }

    /**
     * Process get relative url path image uploaded
     *
     * @return \Magento\Framework\Controller\Result\Raw
     */
    public function execute()
    {

        if (isset($_FILES['image']) && isset($_FILES['image']['name']) && strlen($_FILES['image']['name'])) {

            // check images upload
            try {
                /** @var \Magento\MediaStorage\Model\File\Uploader $uploader */
                $uploader = $this->_uploader->create(
                    ['fileId' => 'image']
                ); // pass property fileId to class Magento\Media\Storeage\Model\File\UploaderFactory

                $uploader->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png']);
                /** @var \Magento\Framework\Image\Adapter\AdapterInterface $imageAdapter */
                $imageAdapter = $this->_adapterFactory->create();

                $uploader->addValidateCallback('image', $imageAdapter, 'validateUploadFile');
                $uploader->setAllowRenameFiles(true);
                $uploader->setFilesDispersion(true);


                /** @var \Magento\Framework\Filesystem\Directory\Read $mediaDirectory */
                $mediaDirectory = $this->_fileSystem->getDirectoryRead(DirectoryList::MEDIA);

                $finalPath = $mediaDirectory->getAbsolutePath(self::BASE_MEDIA_PATH);
                //echo $finalPath; die;
                /** @var  array $result */
                $result = $uploader->save($finalPath);

                $this->_eventManager->dispatch(
                    'ves_banner_gallery_upload_image_after',
                    ['result' => $result, 'action' => $this]
                );

                unset($result['tmp_name']);
                unset($result['path']);

                $result['url'] = $this->getBaseUrl($result['file']);

            } catch (\Exception $e) {
                $result = ['error' => $e->getMessage(), 'errorcode' => $e->getCode()];
            }


            /** @var \Magento\Framework\Controller\Result\Raw $response */
            $response = $this->_resultRawFactory->create();
            $response->setHeader('Content-type', 'text/plain');
            $response->setContents(json_encode($result));

            return $response; //return data image in JSON type

        } else {

            if (isset($data['image']) && isset($data['image']['value'])) {
                if (isset($data['image']['delete'])) {
                    $data['image'] = null;
                    $data['delete_image'] = true;
                } elseif (isset($data['image']['value'])) {
                    $data['image'] = $data['image']['value'];
                } else {
                    $data['image'] = null;
                }
            }
        }

    }


    /**
     * Get url of file uploaded
     *
     * @param $file
     * @return string
     */
    public function getBaseUrl($file)
    {
        return $this->_urlBuilder->getBaseUrl(['_type' => UrlInterface::URL_TYPE_MEDIA]).self::BASE_MEDIA_PATH.$file;
    }

}
