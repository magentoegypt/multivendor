<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsRMA\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Config\ConfigOptionsListConstants;
use Vnecoms\VendorsRMA\Model\Request;

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
     * @var \Vnecoms\Core\Model\ResourceModel\Key\Collection
     */
    protected $_licenseCollection;

    /**
     * @var
     */
    protected $_requestFactory;

    /**
     * @var
     */
    protected $_countRma;

    /**
     * Action constructor.
     * @param \Vnecoms\RMA\Model\RequestFactory $requestFactory
     */
    public function __construct(\Vnecoms\RMA\Model\RequestFactory $requestFactory)
    {
        $om = \Magento\Framework\App\ObjectManager::getInstance();

        $this->_actionFlag = $om->create('Magento\Framework\App\ActionFlag');
        $this->messageManager = $om->create('Magento\Framework\Message\ManagerInterface');
        $this->_session = $om->create('Magento\Backend\Model\Auth\Session');
        $this->_cacheManager = $om->create('\Magento\Framework\App\CacheInterface');
        $this->_requestFactory = $requestFactory->create();

        $resource = $this->_requestFactory->getResource();

        $connection = $resource->getConnection();
        $select = $connection->select();
        $select->from(
            $resource->getTable('ves_rma_request_entity'),
            ['total_rma' => 'count( entity_id )']
        )->where(
            'status  = :status'
        );
        $bind = ['status' => Request::STATE_BEING];

        $this->_countRma  = $connection->fetchOne($select,$bind);
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
            $controllerModule = $action->getRequest()->getControllerModule();

            try {
                if ($action instanceof \Magento\Backend\App\AbstractAction
                ) {
                    /*Show error message on all vnecoms page and dashboard*/
                    if ($moduleName == 'admin' &&
                            $action->getRequest()->getControllerName() == 'dashboard'
                        ) {

                        if ($this->_countRma > 0) {
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
