<?php

namespace Vnecoms\VendorsProductImportExport\Controller\Vendors\Import\Media;

use Magento\Framework\App\Filesystem\DirectoryList;

class Delete extends \Vnecoms\Vendors\Controller\Vendors\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_Vendors::product_import_media';

    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected $resultRawFactory;

    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    protected $_file;

    /**
     * Delete constructor.
     * @param \Vnecoms\Vendors\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     * @param \Magento\Framework\Filesystem\Driver\File $file
     */
    public function __construct(
        \Vnecoms\Vendors\App\Action\Context $context,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Magento\Framework\Filesystem\Driver\File $file
    ) {
        parent::__construct($context);
        $this->resultRawFactory = $resultRawFactory;
        $this->_file = $file;
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

            $path = 'vnecoms_import/'.$this->_session->getVendor()->getVendorId();
            foreach ($deletedFiles as $file) {
                $fileName = $mediaDirectory->getAbsolutePath($path.'/'.$file);

                $this->_eventManager->dispatch(
                    'vendor_import_image_delete_before',
                    ["file_name" => $path.'/'.$file]
                );

                $this->_file->deleteFile($fileName);
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
