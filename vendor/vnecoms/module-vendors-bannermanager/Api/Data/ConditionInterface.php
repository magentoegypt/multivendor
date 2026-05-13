<?php
/**
* Copyright 2017 Vnecoms. All rights reserved.
* See LICENSE.txt for license details.
*/
namespace Vnecoms\BannerManager\Api\Data;

/**
 * Condition interface
 * @api
 */
interface ConditionInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{
    /**#@+
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    const TYPE = 'type';
    const CONDITIONS = 'conditions';
    const AGGREGATOR = 'aggregator';
    const OPERATOR = 'operator';
    const ATTRIBUTE = 'attribute';
    const VALUE = 'value';
    const VALUE_TYPE = 'value_type';
    /**#@-*/

    /**
     * Get type
     *
     * @return string|null
     */
    public function getType();

    /**
     * Get conditions
     *
     * @return \Vnecoms\BannerManager\Api\Data\ConditionInterface[]|null
     */
    public function getConditions();

    /**
     * Get aggregator
     *
     * @return string|null
     */
    public function getAggregator();

    /**
     * Get operator
     *
     * @return string|null
     */
    public function getOperator();

    /**
     * Get attribute
     *
     * @return string|null
     */
    public function getAttribute();

    /**
     * Get value
     *
     * @return mixed
     */
    public function getValue();

    /**
     * Get value type
     *
     * @return string
     */
    public function getValueType();

    /**
     * Set type
     *
     * @param string $type
     * @return $this
     */
    public function setType($type);

    /**
     * Set conditions
     *
     * @param \Vnecoms\BannerManager\Api\Data\ConditionInterface[]|null $conditions
     * @return $this
     */
    public function setConditions(array $conditions = null);

    /**
     * Set aggregator
     *
     * @param string $aggregator
     * @return $this
     */
    public function setAggregator($aggregator);

    /**
     * Set operator
     *
     * @param string $operator
     * @return $this
     */
    public function setOperator($operator);

    /**
     * Set attribute
     *
     * @param string $attribute
     * @return $this
     */
    public function setAttribute($attribute);

    /**
     * Set value
     *
     * @param mixed $value
     * @return $this
     */
    public function setValue($value);

    /**
     * Set value type
     *
     * @param string $valueType
     * @return $this
     */
    public function setValueType($valueType);

    /**
     * Retrieve existing extension attributes object or create a new one
     *
     * @return \Vnecoms\BannerManager\Api\Data\ConditionExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object
     *
     * @param \Vnecoms\BannerManager\Api\Data\ConditionExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Vnecoms\BannerManager\Api\Data\ConditionExtensionInterface $extensionAttributes
    );
}
