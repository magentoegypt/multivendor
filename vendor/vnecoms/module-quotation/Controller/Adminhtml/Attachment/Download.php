<?php

namespace Vnecoms\Quotation\Controller\Adminhtml\Attachment;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Class with class map capability
 *
 * ...
 */
class Download extends \Magento\Backend\App\Action implements HttpGetActionInterface
{

    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected $mediaDirectory;

    /**
     * @var \Magento\Framework\Filesystem\Directory\ReadInterface
     */
    protected $resultRawFactory;

    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    protected $fileFactory;

    /**
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->resultRawFactory      = $resultRawFactory;
        $this->fileFactory           = $fileFactory;
        $this->mediaDirectory = $filesystem->getDirectoryRead(DirectoryList::MEDIA);
        parent::__construct($context);
    }

    public function execute()
    {
        $filePath = base64_decode($this->getRequest()->getParam('file', 0));
        $fileName = explode('/', $filePath);
        $fileName = end($fileName);
        $isFileAvailable = $this->mediaDirectory->isReadable($this->mediaDirectory->getRelativePath('vnecoms_quotation', $filePath));
        if (!$isFileAvailable) {
            throw new \Exception('File not exist');
        }
        return $this->fileFactory->create(
            $fileName,
            [
                'type'=>'filename',
                'value' => "vnecoms_quotation".$filePath
            ],
            \Magento\Framework\App\Filesystem\DirectoryList::MEDIA,
            'application/force-download'
        );
    }
}
