<?php

declare(strict_types=1);

namespace Vnecoms\VendorsSalesGraphQl\Model\Resolver\FilterArgument;

use Magento\Framework\GraphQl\Config\Element\InterfaceType;
use Magento\Framework\GraphQl\Config\Element\Type;
use Magento\Framework\GraphQl\ConfigInterface;
use Magento\Framework\GraphQl\Query\Resolver\Argument\FieldEntityAttributesInterface;

/**
 * Class OrderEntityAttributesForAst
 * @package Vnecoms\VendorsSalesGraphQl\Model\Resolver\FilterArgument
 */
class OrderEntityAttributesForAst implements FieldEntityAttributesInterface
{
    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var array
     */
    private $additionalAttributes = [];

    /**
     * @param ConfigInterface $config
     * @param array           $additionalAttributes
     */
    public function __construct(
        ConfigInterface $config,
        array $additionalAttributes = []
    ) {
        $this->config               = $config;
        $this->additionalAttributes = array_merge($this->additionalAttributes, $additionalAttributes);
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityAttributes(): array
    {
        $orderTypeSchema = $this->config->getConfigElement('VendorOrder');
        if (!$orderTypeSchema instanceof Type) {
            throw new \LogicException(__("Order type not defined in schema."));
        }

        $fields = [];
        foreach ($orderTypeSchema->getInterfaces() as $interface) {
            /** @var InterfaceType $configElement */
            $configElement = $this->config->getConfigElement($interface['interface']);

            foreach ($configElement->getFields() as $field) {
                $fields[$field->getName()] = [
                    'type'      => 'String',
                    'fieldName' => $field->getName(),
                ];
            }
        }

        foreach ($this->additionalAttributes as $attributeName) {
            $fields[$attributeName] = [
                'type'      => 'String',
                'fieldName' => $attributeName,
            ];
        }

        return $fields;
    }
}
