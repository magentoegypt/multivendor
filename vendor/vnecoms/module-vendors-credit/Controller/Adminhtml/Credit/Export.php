<?php

namespace Vnecoms\VendorsCredit\Controller\Adminhtml\Credit;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\Registry;

/**
 * Class Key.
 *
 * @author Vnecoms team <vnecoms.com>
 */
abstract class Export extends Action
{
    /**
     * Core registry.
     *
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @var RedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * date filter.
     *
     * @var \Magento\Framework\Stdlib\DateTime\Filter\Date
     */
    protected $dateFilter;

    /**
     * @var \Magento\Framework\View\Result\LayoutFactory
     */
    protected $resultLayoutFactory;

    /**
     * File Factory.
     *
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    protected $_fileFactory;

    /**
     * Key constructor.
     *
     * @param Registry                                         $registry
     * @param KeyFactory                                       $keyFactory
     * @param RedirectFactory                                  $resultRedirectFactory
     * @param Context                                          $context
     * @param \Magento\Framework\View\Result\LayoutFactory     $resultLayoutFactory
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     */
    public function __construct(
        Registry $registry,
        Context $context,
        \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory
    ) {
        $this->coreRegistry = $registry;
        $this->_fileFactory = $fileFactory;
        $this->resultLayoutFactory = $resultLayoutFactory;

        parent::__construct($context);
    }


    public function initVendor() {
        $id = $this->getRequest()->getParam('id');
        $model = $this->_objectManager->create('Vnecoms\Vendors\Model\Vendor');

        if ($id) {
            $model->load($id);
            if (!$model->getEntityId() || $model->getStatus()==0) {
                $this->messageManager->addError(__('This Seller no longer exists.'));
                $this->_redirect('vendors/*');
                return;
            }
        }
        $this->coreRegistry->register('current_vendor', $model);
    }
}
