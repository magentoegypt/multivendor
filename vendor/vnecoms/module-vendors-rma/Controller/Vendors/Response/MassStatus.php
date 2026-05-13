<?php
/**
 * Created by PhpStorm.
 * User: nvhai
 * Date: 12/21/2016
 * Time: 05:01 PM
 */
namespace Vnecoms\VendorsRMA\Controller\Vendors\Response;

use Magento\Framework\Controller\ResultFactory;
use Vnecoms\Vendors\Controller\Vendors\Action;
use Vnecoms\Vendors\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Vnecoms\RMA\Model\ResourceModel\Reponse\CollectionFactory;
use Vnecoms\RMA\Model\ReponseFactory;

/**
 * Class MassDisable
 */
class MassStatus extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_Vendors::rma_response';
    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * massStatus constructor.
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param Reponse $reponse
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        ReponseFactory $reponse
    ) {
        $this->filter = $filter;
        $this->_model = $reponse;
        $this->collectionFactory = $collectionFactory->create();
        parent::__construct($context);
    }

    /**
     * Execute action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     * @throws \Magento\Framework\Exception\LocalizedException|\Exception
     */
    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory);
        $collectionSize = $collection->getSize();
        $reponseIds = $collection->getAllIds();
        $status = (int) $this->getRequest()->getParam('status');
        $model = $this->_model->create();
        try {
            foreach ($reponseIds as $id){
                $model->load($id);
                $model->setStatus($status);
                $model->save();
            }
            $this->messageManager->addSuccess(__('A total of %1 record(s) have been updated.', $collectionSize));
//            $this->_productPriceIndexerProcessor->reindexList($productIds);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
        } catch (\Exception $e) {
            $this->_getSession()->addException($e, __('Something went wrong while updating the reponse(s) status.'));
        }

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*');
    }
}
