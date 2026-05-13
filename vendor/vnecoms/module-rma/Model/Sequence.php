<?php
namespace Vnecoms\RMA\Model;

use Magento\Framework\App\ResourceConnection as AppResource;
use Magento\Framework\DB\Sequence\SequenceInterface;
use Magento\SalesSequence\Model\Meta;
use Vnecoms\RMA\Helper\Config as Helper;

/**
 * Class Sequence represents sequence in logic
 */
class Sequence implements SequenceInterface
{
    /**
     * Default pattern for Sequence
     */
    const DEFAULT_PATTERN  = "%s%'.09d%s";

    /**
     * @var string
     */
    private $lastIncrementId;


    /**
     * @var false|\Magento\Framework\DB\Adapter\AdapterInterface
     */
    private $connection;

    /**
     * @var \Vnecoms\RMA\Helper\Config
     */
    protected $_helper;

    /**
     * @param Helper $helper
     * @param unknown $pattern
     */
    public function __construct(
        AppResource $resource,
        Helper $helper,
        $pattern = self::DEFAULT_PATTERN
    ) {
        $this->connection = $resource->getConnection('sales');
        $this->_helper = $helper;
    }


    /**
     * Get sequence table
     *
     * @return string
     */
    public function getSequenceTable()
    {
        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $connection = $object_manager->create('\Magento\Framework\App\ResourceConnection');
        return $connection->getTableName('ves_rma_request_num');
    }

    /**
     * Get pattern
     *
     * @return string
     */
    public function getPattern()
    {
        $numberLength = $this->_helper->getIncrementNumber();
        return $numberLength?"%s%'.0".$numberLength."d%s":"%s%d%s";
    }

    /**
     * Process Variables
     *
     * @param unknown $text
     * @return string
     */
    public function processVariables($text)
    {
        /*
            $dateTime = $this->_localeDate->date();
            $text = str_replace("{yyyy}", $dateTime->format('Y') , $text);
            $text = str_replace("{yy}", $dateTime->format('y'), $text);
            $text = str_replace("{mm}", $dateTime->format('m'), $text);
            $text = str_replace("{m}", $dateTime->format('n'), $text);
            $text = str_replace("{dd}", $dateTime->format('d'), $text);
            $text = str_replace("{d}", $dateTime->format('j'), $text);
        */
        return $text;
    }
    /**
     * Retrieve current value
     *
     * @return string
     */
    public function getCurrentValue()
    {
        if (!isset($this->lastIncrementId)) {
            return null;
        }

        $prefix = $this->processVariables(
            $this->_helper->getIncrementPrefix()
        );
        $suffix = $this->processVariables(
            $this->_helper->getIncrementSuffix()
        );
        return sprintf(
            $this->getPattern(),
            $prefix,
            $this->calculateCurrentValue(),
            $suffix
        );
    }

    /**
     * Retrieve next value
     *
     * @return string
     */
    public function getNextValue()
    {
        $select = $this->connection->select();
        $select->from(
            $this->getSequenceTable(),
            ['sequence_value']
        );

        $counter = $this->connection->fetchOne($select);

        if (!$counter) {
            $counter = 1;
            $this->connection->insert(
                $this->getSequenceTable(),
                [
                    'sequence_value' => $counter
                ]
            );
        } else {
            $counter ++;
            $this->connection->update(
                $this->getSequenceTable(),
                ['sequence_value' => $counter]
            );
        }

        $this->lastIncrementId = $counter;

        return $this->getCurrentValue();
    }

    /**
     * Calculate current value depends on start value
     *
     * @return string
     */
    private function calculateCurrentValue()
    {
        $step = $this->_helper->getIncrementStep();
        $start = $this->_helper->getIncrementStartNumber();

        return $start + ($this->lastIncrementId - 1) * $step;
    }

    /**
     * Reset the counter of current store.
     */
    public function resetCounter()
    {
        $this->connection->delete(
            $this->getSequenceTable(),
            'entity_id > 0'
        );
    }
}
