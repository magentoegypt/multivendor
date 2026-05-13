<?php
/**
 * Created by PhpStorm.
 * User: nvhai
 * Date: 12/23/2016
 * Time: 11:13 AM
 */
namespace Vnecoms\VendorsShippingTableRate\Controller\Vendors\Tablerate;

use Vnecoms\Vendors\App\Action\Context;
use Magento\Cms\Controller\Adminhtml\Page\PostDataProcessor;
use Magento\Framework\App\Request\DataPersistorInterface;

class Import extends \Vnecoms\Vendors\Controller\Vendors\Action
{


    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var PostDataProcessor
     */
    protected $dataProcessor;

    /**
     * @var RateViewAuthorizationInterface
     */
    protected $rateAuthorization;
    /**
     * Save constructor.
     * @param Context $context
     * @param PostDataProcessor $dataProcessor
     */
    public function __construct(
        Context $context,
        PostDataProcessor $dataProcessor,
        RateViewAuthorization $rateViewAuthorization,
        DataPersistorInterface $dataPersistor
    ){
        $this->dataProcessor = $dataProcessor;
        $this->rateAuthorization         = $rateViewAuthorization;
        $this->dataPersistor = $dataPersistor;
        parent::__construct($context);
    }
    /**
     * @return void
     */
    public function execute()
    {
        $this->_initAction();
        $title = $this->_view->getPage()->getConfig()->getTitle();
        $title->prepend(__("Shipping Table Rates"));

        $breadcrumb =  __('Import Rate');
        $this->_addBreadcrumb($breadcrumb, $breadcrumb);
        $this->_view->renderLayout();
    }
}