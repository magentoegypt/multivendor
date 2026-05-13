<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\Quotation\Controller\Adminhtml\Quote\Create;

use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\RawFactory;

class ShowUpdateResult extends \Vnecoms\Quotation\Controller\Adminhtml\Quote\Create implements HttpGetActionInterface
{
    /**
     * @var RawFactory
     */
    protected $resultRawFactory;

    /**
     * @param Action\Context $context
     * @param \Magento\Catalog\Helper\Product $productHelper
     * @param \Magento\Framework\Escaper $escaper
     * @param PageFactory $resultPageFactory
     * @param ForwardFactory $resultForwardFactory
     * @param RawFactory $resultRawFactory
     */
    public function __construct(
        Action\Context $context,
        \Magento\Catalog\Helper\Product $productHelper,
        PageFactory $resultPageFactory,
        ForwardFactory $resultForwardFactory,
        RawFactory $resultRawFactory,
        \Vnecoms\Quotation\Model\QuoteRepository $quoteRepository

    ) {
        $this->resultRawFactory = $resultRawFactory;
        parent::__construct(
            $context,
            $productHelper,
            $resultPageFactory,
            $resultForwardFactory,
            $quoteRepository
        );
    }

    /**
     * Show item update result from loadBlockAction
     * to prevent popup alert with resend data question
     *
     * @return \Magento\Framework\Controller\Result\Raw
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Raw $resultRaw */
        $resultRaw = $this->resultRawFactory->create();
        $session = $this->_session;
        if ($session->hasUpdateResult() && is_scalar($session->getUpdateResult())) {
            $resultRaw->setContents($session->getUpdateResult());
        }
        $session->unsUpdateResult();
        return $resultRaw;
    }
}
