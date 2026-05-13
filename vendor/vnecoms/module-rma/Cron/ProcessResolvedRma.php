<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Cron;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;

class ProcessResolvedRma
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var \Vnecoms\RMA\Model\ResourceModel\Request\CollectionFactory
     */
    protected $_rmaCollection;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    /**
     * @var \Magento\Eav\Model\Config
     */
    protected $_eavConfig;

    /**
     * @var \Vnecoms\VendorsRMA\Helper\Data
     */
    protected $_rmaHelper;

    /**
     * @var \Vnecoms\RMA\Model\StatusFactory
     */
    protected $status;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Vnecoms\RMA\Model\ResourceModel\Request\CollectionFactory $requestCollection,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Eav\Model\Config $eavConfig,
        \Vnecoms\RMA\Helper\Config $rmaHelper,
        \Vnecoms\RMA\Model\StatusFactory $status
    ) {
        $this->status        = $status;
        $this->scopeConfig = $scopeConfig;
        $this->_rmaCollection = $requestCollection;
        $this->logger = $logger;
        $this->_eavConfig = $eavConfig;
        $this->_rmaHelper = $rmaHelper;
    }

    /**
     * Process all request with max time escalate
     *
     * @return void
     */
    public function execute()
    {
        $status = $this->status->create()->load(\Vnecoms\VendorsRMA\Model\Request::STATUS_RETURNED, "code");

        $requests = $this->_rmaCollection->create()
            ->addAttributeToFilter("status", $status->getId());

        $status = $this->status->create()->load(\Vnecoms\VendorsRMA\Model\Request::STATUS_RESOLVED, "code");

        $max_time =  $this->_rmaHelper->getMaximumTimeResolvedRequest();

        foreach ($requests as $request) {
            if (time() - strtotime($request->getData('updated_at')) > $max_time*24*60*60) {
                $request->setData('status', $status->getId());
                try {
                    $request->save();
                } catch (\Exception $e) {
                }
            }
        }
        return true;
    }
}
