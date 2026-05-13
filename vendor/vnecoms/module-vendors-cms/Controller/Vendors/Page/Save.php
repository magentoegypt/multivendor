<?php


namespace Vnecoms\VendorsCms\Controller\Vendors\Page;

use Vnecoms\Vendors\Controller\Vendors\Action;
use Vnecoms\VendorsCms\Model\Page;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;

class Save extends \Vnecoms\Vendors\Controller\Vendors\Action
{
    /**
     * Authorization level of a basic admin session.
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_VendorsCms::page_action_save';

    /**
     * @var PostDataProcessor
     */
    protected $dataProcessor;

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /** @var \Vnecoms\VendorsCms\Model\PageFactory  */
    protected $pageFactory;

    /**
     * Save constructor.
     *
     * @param \Vnecoms\Vendors\App\Action\Context   $context
     * @param \Vnecoms\VendorsCms\Model\PageFactory $pageFactory
     * @param PostDataProcessor                     $dataProcessor
     * @param DataPersistorInterface                $dataPersistor
     */
    public function __construct(
        \Vnecoms\Vendors\App\Action\Context $context,
        \Vnecoms\VendorsCms\Model\PageFactory $pageFactory,
        PostDataProcessor $dataProcessor,
        DataPersistorInterface $dataPersistor
    ) {
        $this->dataProcessor = $dataProcessor;
        $this->pageFactory = $pageFactory;
        $this->dataPersistor = $dataPersistor;
        parent::__construct($context);
    }

    /**
     * Save action.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($data) {
            $data = $this->dataProcessor->filter($data);
            $data = $this->dataProcessor->addVendorIdData($data);
            if (isset($data['is_active']) && $data['is_active'] === 'true') {
                $data['is_active'] = Page::STATUS_ENABLED;
            }
            if (empty($data['page_id'])) {
                $data['page_id'] = null;
            }

            if (empty($data['sort_order'])) {
                $data['sort_order'] = 0;
            }

/** @var \Vnecoms\VendorsCms\Model\Page $model */
            //$model = $this->_objectManager->create('Vnecoms\VendorsCms\Model\Page');
            $model = $this->pageFactory->create();

            $id = $this->getRequest()->getParam('page_id');
            if ($id) {
                $model->load($id);
            }

            $model->setData($data);

            $this->_eventManager->dispatch(
                'vendor_cms_page_prepare_save',
                ['page' => $model, 'request' => $this->getRequest()]
            );

            if (!$this->dataProcessor->validate($data)) {
                return $resultRedirect->setPath('*/*/edit', ['page_id' => $model->getId(), '_current' => true]);
            }
            try {
                $model->save();
                $this->messageManager->addSuccess(__('You saved the page.'));
                $this->dataPersistor->clear('cms_page');
                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['page_id' => $model->getId(), '_current' => true]);
                }

                return $resultRedirect->setPath('*/*/');
            } catch (LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                var_dump($e->getMessage());exit;
                $this->messageManager->addException($e, __('Something went wrong while saving the page.'));
            }

            $this->dataPersistor->set('cms_page', $data);

            return $resultRedirect->setPath('*/*/edit', ['page_id' => $this->getRequest()->getParam('page_id')]);
        }

        return $resultRedirect->setPath('*/*/');
    }
}
