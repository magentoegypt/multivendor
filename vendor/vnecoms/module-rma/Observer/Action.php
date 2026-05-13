<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\RMA\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Config\ConfigOptionsListConstants;
use Vnecoms\RMA\Model\Request;

class Action implements ObserverInterface
{
    /**
     * @var \Magento\Framework\App\ActionFlag
     */
    protected $_actionFlag;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $_session;

    /**
     * @var array
     */
    protected $_extensionsList;

    /**
     * Application Cache Manager.
     *
     * @var \Magento\Framework\App\CacheInterface
     */
    protected $_cacheManager;

    /**
     * @var \Vnecoms\RMA\Model\RequestFactory
     */
    protected $_requestFactory;

    /**
     * @var \Vnecoms\Core\Model\ResourceModel\Key\Collection
     */
    protected $_licenseCollection;

    /**
     * Action constructor.
     * @param \Vnecoms\RMA\Model\RequestFactory $requestFactory
     * @param \Magento\Framework\App\ActionFlag $_actionFlag
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Backend\Model\Auth\Session $session
     * @param \Magento\Framework\App\CacheInterface $_cacheManager
     */
    public function __construct(
        \Vnecoms\RMA\Model\RequestFactory $requestFactory,
        \Magento\Framework\App\ActionFlag $_actionFlag,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Backend\Model\Auth\Session $session,
        \Magento\Framework\App\CacheInterface $_cacheManager
    )
    {
        $this->_actionFlag = $_actionFlag;
        $this->messageManager = $messageManager;
        $this->_session = $session;
        $this->_cacheManager = $_cacheManager;
        $this->_requestFactory = $requestFactory;
    }

    /**
     * @return mixed
     */
    protected function getCountRma() {
        $state = [Request::STATE_CANCELED,Request::STATE_CLOSED];
        $resource = $this->_requestFactory->create()->getResource();
        $connection = $resource->getConnection();
        $select = $connection->select();
        $select->from(
            $resource->getTable('ves_rma_request_entity'),
            ['total_rma' => 'count( entity_id )']
        )->where(
            'status NOT IN(:status)'
        );
        $bind = ['status' => implode(",", $state)];
        return $connection->fetchOne($select,$bind);
    }

    /**
     * Add free gift to shopping cart.
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->_session->isLoggedIn()) {
            return;
        }
        /* @var \Magento\Framework\App\Action\AbstractAction */
        $action = $observer->getControllerAction();

        /*Do with get request only*/
        if ($action->getRequest()->isGet()) {
            $moduleName = $action->getRequest()->getModuleName();

            try {
                if ($action instanceof \Magento\Backend\App\AbstractAction
                ) {
                    /*Show error message on all vnecoms page and dashboard*/
                    if ($moduleName == 'admin' &&
                        $action->getRequest()->getControllerName() == 'dashboard'
                    ) {
                        $countRma = $this->getCountRma();
                        if ($countRma) {
                            $this->messageManager->addNotice(__(
                                "You have %1 processed rma.%2",
                                $this->_countRma,
                                '<a href="'.$action->getUrl('vrma/request/open').'">'.__('Open Requests').'</a>'
                            ));
                        }
                    }

                    /* Redirect admin to license page in these case:
                     * - Admin first time login to admin panel
                     * - Admin access to an inactive extension
                     */
                }
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
        }
    }
}
