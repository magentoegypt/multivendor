<?php
/**
 *
 * Copyright © Vnecoms. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsRMA\Controller\Vendors\Request;

use Vnecoms\Vendors\Controller\Vendors\Action;
use Vnecoms\Vendors\App\Action\Context;

class Download extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_Vendors::rma_request';

    public function __construct(
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        Context $context
    ) {
        $this->resultRawFactory      = $resultRawFactory;
        $this->fileFactory           = $fileFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $file = $this->getRequest()->getParam('file',0);
        //do your custom stuff here
        $fileName = base64_decode($file);
        return $this->fileFactory->create(
            $fileName,
            [
                'type'=>'filename',
                'value' => "vrma/request/".$fileName
            ], //content here. it can be null and set later
            \Magento\Framework\App\Filesystem\DirectoryList::MEDIA,
            'application/force-download'//content type here
        );

    }
}
