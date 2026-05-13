<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsRMA\Cron;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;

class ProcessEscalateRma
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
        \Vnecoms\VendorsRMA\Helper\Data $rmaHelper,
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
        $requests = $this->_rmaCollection->create()
            ->addAttributeToFilter("state",\Vnecoms\VendorsRMA\Model\Request::STATE_AWAITING);
        $status = $this->status->create()->load(\Vnecoms\VendorsRMA\Model\Request::STATUS_BEING,"code");
        $max_time =  $this->_rmaHelper->getMaximumTimeEscalateRequest();
        foreach ($requests as $request){
            if(strtotime('now') - strtotime($request->getData('updated_at')) > $max_time*24*60*60){
                $request->setData('status',$status->getId());
                try {
                    $request->save();
                }
                catch (Exception $e){
                }
            }
        }
        return true;
    }

}
