<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsPriceComparison\Cron;

class ProcessMainProduct
{
    const LIMIT_PROCESS = 3;

    /**
     * @var \Vnecoms\VendorsPriceComparison\Model\Process
     */
    protected $process;

    /**
     * @var \Vnecoms\VendorsPriceComparison\Model\Queue
     */
    protected $queue;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    /**
     * @var \Magento\Catalog\Model\Indexer\Product\Full
     */
    protected $reIndex;

    /**
     * ProcessMainProduct constructor.
     * @param \Vnecoms\VendorsPriceComparison\Model\Process $process
     * @param \Vnecoms\VendorsPriceComparison\Model\QueueFactory $queue
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Catalog\Model\Indexer\Product\Full $reIndex
     */
    public function __construct(
        \Vnecoms\VendorsPriceComparison\Model\Process $process,
        \Vnecoms\VendorsPriceComparison\Model\QueueFactory $queue,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Catalog\Model\Indexer\Product\Full $reIndex
    ) {
        $this->process  = $process;
        $this->queue = $queue;
        $this->timezone = $timezone;
        $this->reIndex = $reIndex;
    }

    /**
     * Process all request with max time escalate
     *
     * @return void
     */
    public function execute()
    {
        $queueCollections = $this->queue->create()->getCollection();
        $queueCollections->setOrder('created_at','ASC');
        $queueCollections->setPageSize(self::LIMIT_PROCESS);
        $nowTime = $this->timezone->date()->format('Y-m-d H:i:s');
        $productIds = [];
        
        foreach ($queueCollections as $queue) {
            $productIds[] = $queue->getSellProductId();
            try {
                switch ($queue->getType()) {
                    case 'delete':
                        $product = $this->process->deleteMainProduct($queue->getSellProductId());
                        if ($product) {
                            $productIds[] = $product->getId();
                        }
                        break;
                    case 'update':
                        $product =  $this->process->updateMainProduct($queue->getSellProductId());
                        if ($product) {
                            $productIds[] = $product->getId();
                        }
                        break;
                }
                $queue->delete();
            } catch (\Exception $e) {
                $queue->setData('created_at', $nowTime)->save();
            }
        }
        $this->reIndex->executeList(array_unique($productIds));

    }

}
