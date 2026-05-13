<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsShippingTableRate\Controller\Vendors;

use Magento\Framework\View\Result\PageFactory;
/**
 * Adminhtml tax rate controller
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
abstract class Tablerate extends \Vnecoms\Vendors\Controller\Vendors\Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Vnecoms\Vendors\Model\Session
     */
    protected $vendorSession;


    /** @var \Magento\Framework\Controller\Result\JsonFactory  */
    protected $resultJsonFactory;

    /**
     * @var RateViewAuthorizationInterface
     */
    protected $rateAuthorization;

    /** @var \Vnecoms\VendorsShippingTableRate\Model\TablerateRepository  */
    protected $rateRepository;


    /** @var \Magento\Framework\Api\DataObjectHelper  */
    protected $dataObjectHelper;


    /**
     * @param \Vnecoms\Vendors\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     */
    public function __construct(
        \Vnecoms\Vendors\App\Action\Context $context,
        Tablerate\RateViewAuthorizationInterface $rmaAuthorization,
        PageFactory $resultPageFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Vnecoms\VendorsShippingTableRate\Model\TablerateRepository $rateRepository,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->rateAuthorization = $rmaAuthorization;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->rateRepository = $rateRepository;
        $this->dataObjectHelper = $dataObjectHelper;
        parent::__construct($context);
    }


    /**
     * Init layout, menu and breadcrumb
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    protected function _initAction()
    {
        parent::_initAction();
        $this->_addBreadcrumb(__('Shipping'), __('Table Rate'));
        return $this;
    }

    /**
     * @return \Vnecoms\Vendors\Model\Vendor
     */
    public function getVendor()
    {
        if ($this->vendorSession) {
            return $this->vendorSession->getVendor();
        } else {
            $this->vendorSession = $this->_objectManager->get('Vnecoms\Vendors\Model\Session');

            return $this->vendorSession->getVendor();
        }
    }
}
