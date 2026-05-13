<?php

namespace Vnecoms\RMA\Block\Frontend\Guest;

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
        if (!($postRma = $this->_customerSession->getPostRma())) {
            return [];
        }
        if ($this->_requestCollection) {
            $postRma = $this->_customerSession->getPostRma();
            $this->_requestCollection->addFieldToSelect(
                '*'
            )->addFieldToFilter(
                'order_incremental_id',
                $postRma['order_incremental_id']
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
        return $this->getUrl('vrma/guest/new');
    }
    /*
    public function isCancelRma($state){
        $check = false;
        if($state == \Vnecoms\RMA\Model\Request::STATE_OPEN ) $check = true;
        return $check;
    }
    */
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

    /**
     * get View
     * @param $id
     * @return mixed
     */
    public function getViewUrl($request)
    {
        return $this->getUrl('vrma/guest/view', ["id"=>$request->getId()]);
    }
    /**
     * get Logout URl
     * @param $id
     * @return mixed
     */
    public function getLogOut()
    {
        return $this->getUrl('vrma/guest/logout');
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
