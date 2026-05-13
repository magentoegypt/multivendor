<?php
/**
 * Copyright (c) 2017 Vnecoms Co ltd. All rights reserved.
 */
namespace Vnecoms\Quotation\Controller\Adminhtml\Quote;

use Magento\Backend\App\Action;
use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\Model\View\Result\ForwardFactory;

abstract class Create extends \Magento\Backend\App\Action
{
    const PERCENT = 'percent';

    const VALUE = 'value';

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Magento\Backend\Model\View\Result\ForwardFactory
     */
    protected $resultForwardFactory;

    /**
     * @var \Vnecoms\Quotation\Model\QuoteRepository
     */
    protected $quoteRepository;

    /**
     * @param Action\Context $context
     * @param \Magento\Catalog\Helper\Product $productHelper
     * @param \Magento\Framework\Escaper $escaper
     * @param PageFactory $resultPageFactory
     * @param ForwardFactory $resultForwardFactory
     */
    public function __construct(
        Action\Context $context,
        \Magento\Catalog\Helper\Product $productHelper,
        PageFactory $resultPageFactory,
        ForwardFactory $resultForwardFactory,
        \Vnecoms\Quotation\Model\QuoteRepository $quoteRepository
    ) {
        parent::__construct($context);
        $productHelper->setSkipSaleableCheck(true);
        $this->quoteRepository = $quoteRepository;
        $this->resultPageFactory = $resultPageFactory;
        $this->resultForwardFactory = $resultForwardFactory;
    }

    /**
     * Acl check for admin
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed($this->_getAclResource());
    }

    /**
     * Get acl resource
     *
     * @return string
     */
    protected function _getAclResource()
    {
        $action = strtolower($this->getRequest()->getActionName());
        switch ($action) {
            case 'index':
            case 'save':
                $aclResource = 'Vnecoms_Quotation::save';
                break;
            case 'cancel':
                $aclResource = 'Vnecoms_Quotation::save';
                break;
            default:
                $aclResource = 'Vnecoms_Quotation::main';
                break;
        }
        return $aclResource;
    }

    protected function _processProposals($value, $type = self::PERCENT)
    {

    }
}
