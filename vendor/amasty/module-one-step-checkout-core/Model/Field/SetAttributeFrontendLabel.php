<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\Field;

use Amasty\CheckoutCore\Model\Field;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Eav\Model\Entity\Attribute\FrontendLabel;
use Magento\Eav\Model\Entity\Attribute\FrontendLabelFactory;

class SetAttributeFrontendLabel
{
    /**
     * @var FrontendLabelFactory
     */
    private $frontendLabelFactory;

    public function __construct(FrontendLabelFactory $frontendLabelFactory)
    {
        $this->frontendLabelFactory = $frontendLabelFactory;
    }

    public function execute(Attribute $attribute, int $storeId, string $label): void
    {
        if ($storeId === Field::DEFAULT_STORE_ID) {
            $attribute->setData(AttributeInterface::FRONTEND_LABEL, $label);
            return;
        }

        foreach ((array) $attribute->getFrontendLabels() as $frontendLabel) {
            if ((int) $frontendLabel->getStoreId() === $storeId) {
                $frontendLabel->setLabel($label);
                $this->updateStoreLabels($attribute, $storeId, $label);
                return;
            }
        }

        /** @var FrontendLabel $frontendLabel */
        $frontendLabel = $this->frontendLabelFactory->create();
        $frontendLabel->setStoreId($storeId);
        $frontendLabel->setLabel($label);

        $attribute->setFrontendLabels(
            !empty($attribute->getFrontendLabels()) ?
                array_merge($attribute->getFrontendLabels(), [$frontendLabel]) :
                [$frontendLabel]
        );

        $this->updateStoreLabels($attribute, $storeId, $label);
    }

    private function updateStoreLabels(Attribute $attribute, int $storeId, string $label): void
    {
        $storeLabels = $attribute->getStoreLabels();
        $storeLabels[$storeId] = $label;
        $attribute->setData('store_labels', $storeLabels);
    }
}
