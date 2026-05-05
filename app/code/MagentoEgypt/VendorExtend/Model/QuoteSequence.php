<?php
namespace MagentoEgypt\VendorExtend\Model;

use Magento\Framework\App\ResourceConnection as AppResource;
use Magento\Framework\DB\Sequence\SequenceInterface;
use Magento\SalesSequence\Model\Meta;
use Vnecoms\Quotation\Helper\Data as Helper;

class QuoteSequence implements SequenceInterface
{
    /**
     * Default pattern for Sequence.
     */
    const DEFAULT_PATTERN = "%s%'.09d%s";

    /**
     * @var string
     */
    private $lastIncrementId;

    /**
     * @var false|\Magento\Framework\DB\Adapter\AdapterInterface
     */
    private $connection;

    /**
     * @var \Vnecoms\Quotation\Helper\Data
     */
    protected $_helper;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_localeDate;

    /**
     * @var string
     */
    private $_entityType;

    /**
     * @var string
     */
    private $_storeId;

    /**
     * @var string
     */
    private $_quoteStoreId;

    /**
     * @var AppResource
     */
    protected $_resource;

    /**
     * @param Meta                                                 $meta
     * @param AppResource                                          $resource
     * @param Helper                                               $helper
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param unknown                                              $pattern
     */
    public function __construct(
        $storeId,
        $quoteStoreId,
        AppResource $resource,
        Helper $helper,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        $pattern = self::DEFAULT_PATTERN
    ) {
        $this->connection = $resource->getConnection('sales');
        $this->_resource = $resource;
        $this->_helper = $helper;
        $this->_localeDate = $localeDate;
        $this->_storeId = $storeId;
        $this->_quoteStoreId = $quoteStoreId ? $quoteStoreId : $storeId;
    }

    /**
     * Get Store Id.
     *
     * @return int
     */
    public function getStoreId()
    {
        return $this->_storeId;
    }

    /**
     * Get sequence table.
     *
     * @return string
     */
    public function getSequenceTable()
    {
        return $this->_resource->getTableName('vnecoms_quotation_quote_num');
    }

    /**
     * Get pattern.
     *
     * @return string
     */
    public function getPattern()
    {
        $numberLength = $this->_helper->getNumberLength(
            $this->getStoreId()
        );

        return $numberLength ? "%s%'.0".$numberLength.'d%s' : '%s%d%s';
    }

    /**
     * Process Variables.
     *
     * @param unknown $text
     *
     * @return string
     */
    public function processVariables($text)
    {
        $dateTime = $this->_localeDate->date();

        if (!$text) return null;

        $text = str_replace('{yyyy}', $dateTime->format('Y'), $text);
        $text = str_replace('{yy}', $dateTime->format('y'), $text);
        $text = str_replace('{mm}', $dateTime->format('m'), $text);
        $text = str_replace('{m}', $dateTime->format('n'), $text);
        $text = str_replace('{dd}', $dateTime->format('d'), $text);
        $text = str_replace('{d}', $dateTime->format('j'), $text);

        return $text;
    }
    /**
     * Retrieve current value.
     *
     * @return string
     */
    public function getCurrentValue()
    {
        if (!isset($this->lastIncrementId)) {
            return;
        }

        $storeId = $this->getStoreId();
        $prefix = $this->processVariables(
            $this->_helper->getPrefix($this->_quoteStoreId)
        );
        $suffix = $this->processVariables(
            $this->_helper->getSuffix($this->_quoteStoreId)
        );

        return sprintf(
            $this->getPattern(),
            $prefix,
            $this->calculateCurrentValue(),
            $suffix
        );
    }

    /**
     * Retrieve next value.
     *
     * @return string
     */
    public function getNextValue()
    {
        $_storeId = 1;
        $select = $this->connection->select();
        $select->from(
            $this->getSequenceTable(),
            ['sequence_value']
        )->where(
            'store_id = :store_id'
        );
        $bind = ['store_id' => $_storeId];

        $counter = $this->connection->fetchOne($select, $bind);

        if (!$counter) {
            $counter = 1;
            $this->connection->insert(
                $this->getSequenceTable(),
                [
                    'store_id' => $_storeId,
                    'sequence_value' => $counter,
                ]
            );
        } else {
            ++$counter;
            $this->connection->update(
                $this->getSequenceTable(),
                ['sequence_value' => $counter],
                'store_id = "'.$_storeId.'"'
            );
        }

        $this->lastIncrementId = $counter;

        return $this->getCurrentValue();
    }

    /**
     * Calculate current value depends on start value.
     *
     * @return string
     */
    private function calculateCurrentValue()
    {
        $step = $this->_helper->getStep($this->getStoreId());
        $start = $this->_helper->getStartValue($this->getStoreId());

        return $start + ($this->lastIncrementId - 1) * $step;
    }

    /**
     * Reset the counter of current store.
     */
    public function resetCounter()
    {
        $this->connection->delete(
            $this->getSequenceTable(),
            'store_id = "'.$this->_storeId.'"'
        );
    }
}
