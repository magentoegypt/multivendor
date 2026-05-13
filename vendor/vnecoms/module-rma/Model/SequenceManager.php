<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Model;

use Magento\SalesSequence\Model\ResourceModel\Meta as ResourceSequenceMeta;
use Vnecoms\RMA\Model\SequenceFactory as SequenceFactory;

/**
 * Class Manager
 */
class SequenceManager
{

    /**
     * @var SequenceFactory
     */
    protected $sequenceFactory;

    /**
     * @param ResourceSequenceMeta $resourceSequenceMeta
     * @param SequenceFactory $sequenceFactory
     */
    public function __construct(
        SequenceFactory $sequenceFactory
    ) {
        $this->sequenceFactory = $sequenceFactory;
    }

    /**
     *
     * @return \Magento\Framework\DB\Sequence\SequenceInterface
     */
    public function getSequence()
    {
        return $this->sequenceFactory->create();
    }
}
