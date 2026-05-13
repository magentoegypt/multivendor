<?php

namespace Vnecoms\Quotation\Model;

use Magento\SalesSequence\Model\ResourceModel\Meta as ResourceSequenceMeta;

/**
 * Class Manager.
 */
class SequenceManager
{
    /**
     * @var SequenceFactory
     */
    protected $sequenceFactory;

    /**
     * @param ResourceSequenceMeta $resourceSequenceMeta
     * @param SequenceFactory      $sequenceFactory
     */
    public function __construct(
        SequenceFactory $sequenceFactory
    ) {
        $this->sequenceFactory = $sequenceFactory;
    }

    /**
     * Returns sequence for given entityType and store.
     *
     * @param int    $storeId      This is used to get the counter.
     * @param int    $quoteStoreId
     *
     * @return \Magento\Framework\DB\Sequence\SequenceInterface
     */
    public function getSequence($storeId, $quoteStoreId)
    {
        return $this->sequenceFactory->create(
            [
                'storeId' => $storeId,
                'quoteStoreId' => $quoteStoreId,
            ]
        );
    }
}
