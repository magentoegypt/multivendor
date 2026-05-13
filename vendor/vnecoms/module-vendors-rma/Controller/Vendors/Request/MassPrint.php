<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsRMA\Controller\Vendors\Request;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Vnecoms\Vendors\App\Action\Context;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Ui\Component\MassAction\Filter;
use Vnecoms\RMA\Model\Pdf\Request as RmaPdf;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Vnecoms\RMA\Model\ResourceModel\Request\CollectionFactory as CollectionFactory;
/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class MassPrint extends \Vnecoms\VendorsRMA\Controller\Vendors\Vendors
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_Vendors::rma_request';
    /**
     * @var Filter
     */
    protected $filter;
    /**
     * @var FileFactory
     */
    protected $fileFactory;

    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * @var RMA
     */
    protected $pdfRma;

    /**
     * @var RequestCollectionFactory
     */
    protected $requestCollectionFactotory;

    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param DateTime $dateTime
     * @param FileFactory $fileFactory
     * @param DefaultRma $rma
     * @param RmaCollectionFactory $shipmentCollectionFactory
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        DateTime $dateTime,
        FileFactory $fileFactory,
        RmaPdf $rma
    ) {
        $this->fileFactory = $fileFactory;
        $this->dateTime = $dateTime;
        $this->pdfRma = $rma;
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context);
    }

    /**
     * Print shipments for selected orders
     *
     * @param AbstractCollection $collection
     * @return ResponseInterface|\Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());

        if (!$collection->getSize()) {
            $this->messageManager->addError(__('There are no printable documents related to selected rmas.'));
            return $this->resultRedirectFactory->create()->setPath("*/*");
        }
        return $this->fileFactory->create(
            sprintf('rma%s.pdf', $this->dateTime->date('Y-m-d_H-i-s')),
            $this->pdfRma->getPdf($collection->getItems())->render(),
            DirectoryList::VAR_DIR,
            'application/pdf'
        );
    }
}
