<?php

namespace Vnecoms\RMA\Block\Frontend\Customer;

class ListRma extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Vnecoms\RMA\Model\ResourceModel\Request\Collection
     */
    protected $_requestCollection;

    /**
     * ListRma constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Vnecoms\RMA\Model\ResourceModel\Request\CollectionFactory $requestCollection
     * @param \Vnecoms\RMA\Model\StatusFactory $statusFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Vnecoms\RMA\Model\ResourceModel\Request\CollectionFactory $requestCollection,
        \Vnecoms\RMA\Model\StatusFactory $statusFactory,
        array $data = []
    ) {
        $this->_customerSession     = $customerSession;
        $this->_requestCollection   = $requestCollection->create();
        $this->_status              = $statusFactory->create();
        parent::__construct($context, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->pageConfig->getTitle()->set(__('Request RMA'));
    }
    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        /** @var \Magento\Theme\Block\Html\Pager $pager */
        $pager = $this->getLayout()->createBlock('Magento\Theme\Block\Html\Pager', 'rma.request.list.pager');
        $pager->setShowPerPage(true)->setCollection($this->getRequestRma());
        $this->setChild('pager', $pager);
        $this->getRequestRma()->load();
        return $this;
    }
    /**
     * @return string
     */
    public function getPagerHtml()
    {
        return $this->getChildHtml('pager');
    }
    /**
     * function name :getTicketsOpens()
     *
     * @param null
     * @return array
     */
    public function getRequestRma()
    {
        if (!($customerId = $this->_customerSession->getCustomerId())) {
            return [];
        }
        if ($this->_requestCollection) {
            $customer = $this->_customerSession->getCustomer();
            $this->_requestCollection->addAttributeToSelect(
                '*'
            )->addFieldToFilter(
                'customer_id',
                $customer->getId()
            )->addFieldToFilter(
                'website_id',
                ['eq' => $this->_storeManager->getStore()->getWebsiteId()]
            )->setOrder(
                'created_at',
                'desc'
            );
        }
        return $this->_requestCollection;
    }
    /**
     * @param object $order
     * @return string
     */
    public function getNewUrl()
    {
        return $this->getUrl('vrma/customer/new');
    }

    /**
     * check if request status cancel
     * @param $request
     * @return bool
     */
    public function isCancelRma($request)
    {
        $check = false;
        //  if($state == \Vnecoms\RMA\Model\Request::STATE_OPEN ) $check = true;
        $status = $request->getStatusObject();
        if ($status->getCode() == \Vnecoms\RMA\Model\Request::STATUS_PENDING
            || $status->getCode() == \Vnecoms\RMA\Model\Request::STATUS_APPROVAL ) {
            $check = true;
        }
        return $check;
    }


    /*
    public function isCancelRma($state){
        $check = false;
        if($state == \Vnecoms\RMA\Model\Request::STATE_OPEN ) $check = true;
        return $check;
    }
    */
    /**
     * get View
     * @param $id
     * @return mixed
     */
    public function getViewUrl($id)
    {
        return $this->getUrl('vrma/customer/view', ['id'=>$id]);
    }

    /**
     * get Status code by status id
     * @param $id
     * @return mixed
     */
    public function getStatusCode($id)
    {
        $status = $this->_status->load($id);
        return $status->getCode();
    }
    /**
     * format date html
     * @param $date
     * @return mixed
     */
    public function getFormatDateHtml($date)
    {
        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $dateObj = $object_manager->get('\Magento\Framework\Stdlib\DateTime\DateTime');
        return $dateObj->date('F j, Y, g:i a', $date);
    }
}
