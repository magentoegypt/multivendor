<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Block\Adminhtml\Field\Edit\Group\Row;

use Amasty\CheckoutCore\Block\Adminhtml\Renderer\Template;
use Amasty\CheckoutCore\Model\Customer\Address\Attribute\CanChangeIfAttributeIsRequired;
use Amasty\CheckoutCore\Model\Field;
use Amasty\CheckoutCore\Model\Field\ConfigManagement\FieldToConfig\GetAttributeCode;
use Magento\Backend\Block\Template\Context;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class Renderer extends Template
{
    /**
     * @var string
     */
    protected $_template = 'Amasty_CheckoutCore::widget/form/renderer/row.phtml';

    /**
     * @var GetAttributeCode
     */
    private $getAttributeCode;

    /**
     * @var CanChangeIfAttributeIsRequired
     */
    private $canChangeIfAttributeIsRequired;

    public function __construct(
        Context $context,
        GetAttributeCode $getAttributeCode,
        CanChangeIfAttributeIsRequired $canChangeIfFieldIsRequired,
        array $data = []
    ) {
        $this->getAttributeCode = $getAttributeCode;
        $this->canChangeIfAttributeIsRequired = $canChangeIfFieldIsRequired;
        parent::__construct($context, $data);
    }

    public function getOrderAttrUrl(int $attributeId): string
    {
        return parent::getUrl('amorderattr/attribute/edit', ['attribute_id' => $attributeId]);
    }

    public function getCustomerAttrUrl(int $attributeId): string
    {
        return parent::getUrl('amcustomerattr/attribute/edit', ['attribute_id' => $attributeId]);
    }

    public function canChangeRequiredCheckbox(Field $field): bool
    {
        $attributeCode = $this->getAttributeCode->execute($field);
        return $attributeCode && $this->canChangeIfAttributeIsRequired->execute($attributeCode);
    }

    public function getRequiredTooltipText(Field $field): ?string
    {
        $attributeCode = $this->getAttributeCode->execute($field);
        if (!$attributeCode) {
            return null;
        }

        switch ($attributeCode) {
            case 'postcode':
                return __(
                    'To configure Postcode requirement for certain countries please check settings at'
                    . ' Stores > Configuration > General > General > Country Options'
                );
            case 'region':
                return __(
                    'To configure State requirement for certain countries please check settings'
                    . ' at Stores > Configuration > General > General > State Options'
                );
            default:
                return __(
                    'To configure which customer attributes will be required to checkout please check settings'
                    . ' at Stores > Configuration > Customers > Customer Configuration > Name and Address Options'
                );
        }
    }
}
