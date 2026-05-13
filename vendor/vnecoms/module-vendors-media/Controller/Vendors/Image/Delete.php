<?php

namespace Vnecoms\VendorsMedia\Controller\Vendors\Image;

use Magento\Framework\App\Filesystem\DirectoryList;

class Delete extends \Vnecoms\Vendors\Controller\Vendors\Action
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
     * @param \Vnecoms\Vendors\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     */
    public function __construct(
        \Vnecoms\Vendors\App\Action\Context $context,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
    ) {
        parent::__construct($context);
        $this->resultRawFactory = $resultRawFactory;
    }
    
    /**
     * @return void
     */
    public function execute()
    {
        try {
            $deletedFiles = $this->getRequest()->getParam('files');
            if (!$deletedFiles) {
                throw new \Exception(__("There is no data to process"));
            }
            
            $deletedFiles = explode(",", $deletedFiles);
            $mediaDirectory = $this->_objectManager->get('Magento\Framework\Filesystem')
                ->getDirectoryRead(DirectoryList::MEDIA);
            $helper= $this->_objectManager->create('Vnecoms\VendorsMedia\Helper\Data');
            $path = $helper->getMediaFolder($this->_session->getVendor());
            foreach ($deletedFiles as $file) {
                $fileName = $mediaDirectory->getAbsolutePath($path.'/'.$file);
                if (!@unlink($fileName)) {
                    throw new \Exception(__("Can not delete the image: %1", $file));
                }
            }
            $result = ['deleted_files' => $deletedFiles];
        } catch (\Exception $e) {
            $result = ['error' => $e->getMessage()];
        }

        /** @var \Magento\Framework\Controller\Result\Raw $response */
        $response = $this->resultRawFactory->create();
        $response->setHeader('Content-type', 'text/plain');
        $response->setContents(json_encode($result));
        return $response;
    }
}
