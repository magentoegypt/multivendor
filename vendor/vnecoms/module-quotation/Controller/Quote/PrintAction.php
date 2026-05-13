<?php
/**
 * Copyright © 2017 Vnecoms. All rights reserved.
 */
namespace Vnecoms\Quotation\Controller\Quote;

use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Filesystem\DirectoryList;

class PrintAction extends \Magento\Framework\App\Action\Action
{
    protected $fileFactory;

    protected $resultForwardFactory;
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;


    public function __construct(
        Context $context,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        PageFactory $resultPageFactory
    ) {
        $this->fileFactory = $fileFactory;
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * Print Order Action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $quoteId = (int)$this->getRequest()->getParam('quote_id');
        if ($quoteId) {
            $quote = $this->_objectManager->create('Vnecoms\Quotation\Api\QuoteRepositoryInterface')->getById($quoteId);
            if ($quote) {
                $pdf = $this->_objectManager->create('Vnecoms\Quotation\Model\Quote\Pdf')->getPdf([$quote]);
                $date = $this->_objectManager->get('Magento\Framework\Stdlib\DateTime\DateTime')->date('Y-m-d_H-i-s');
                return $this->fileFactory->create(
                    'quote' . $date . '.pdf',
                    $pdf->render(),
                    DirectoryList::VAR_DIR,
                    'application/pdf'
                );
            }
        }else {
            return $this->resultForwardFactory->create()->forward('noroute');
        }


    }
}
