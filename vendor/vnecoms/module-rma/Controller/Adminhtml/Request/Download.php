<?php
namespace Vnecoms\RMA\Controller\Adminhtml\Request;

class Download extends \Magento\Backend\App\Action
{
    public function __construct(
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->resultRawFactory      = $resultRawFactory;
        $this->fileFactory           = $fileFactory;
        parent::__construct($context);
    }
    public function execute()
    {
        $file = $this->getRequest()->getParam('file', 0);
        //do your custom stuff here
        $fileName = base64_decode($file);
        return $this->fileFactory->create(
            $fileName,
            [
                'type'=>'filename',
                'value' => "rma/request/".$fileName
            ], //content here. it can be null and set later
            \Magento\Framework\App\Filesystem\DirectoryList::MEDIA,
            'application/force-download'//content type here
        );
    }
    /**
     * Is access to section allowed
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magento_Sales::rma');
    }
}
