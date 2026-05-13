<?php
/**
 * Created by PhpStorm.
 * User: nvhai
 * Date: 12/23/2016
 * Time: 11:13 AM
 */
namespace Vnecoms\VendorsRMA\Controller\Vendors\Response;

use Vnecoms\Vendors\App\Action\Context;
use Magento\Cms\Controller\Adminhtml\Page\PostDataProcessor;
use Magento\Framework\App\Request\DataPersistorInterface;

class Edit extends \Vnecoms\VendorsRMA\Controller\Vendors\Vendors
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_Vendors::rma_response';

    /**
     * @var PostDataProcessor
     */
    protected $dataProcessor;

    /**
     * @var RmaViewAuthorization
     */
    protected $rmaAuthorization;

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * Save constructor.
     * @param Context $context
     * @param PostDataProcessor $dataProcessor
     * @param \Vnecoms\RMA\Model\Reponse $reponse
     */
    public function __construct(
        Context $context,
        PostDataProcessor $dataProcessor,
        RmaViewAuthorization $rmaViewAuthorization,
        DataPersistorInterface $dataPersistor
    ){
        $this->dataProcessor = $dataProcessor;
        $this->rmaAuthorization         = $rmaViewAuthorization;
        $this->dataPersistor = $dataPersistor;
        parent::__construct($context);
    }
    /**
     * @return void
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('reponse_id');
        $model = $this->_objectManager->create('Vnecoms\RMA\Model\Reponse');

        if ($id) {
            $model->load($id);
            if (!$model->getId() || !$this->rmaAuthorization->canView($model)) {
                $this->messageManager->addError(__('This Respons no longer exists.'));
                $this->_redirect('*/*');
                return;
            }
        }

        $this->_coreRegistry->register('current_reponse', $model);

        $this->_initAction();
        $this->setActiveMenu('Vnecoms_Vendors::rma_response');
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Manage Response'));
        $this->_view->getPage()->getConfig()->getTitle()->prepend(
            $model->getId() ? $model->getTitle() : __('New Response')
        );

        $breadcrumb = $id ? __('Edit Reponse') : __('New Response');
        $this->_addBreadcrumb($breadcrumb, $breadcrumb);
        $this->_view->renderLayout();
    }
}
