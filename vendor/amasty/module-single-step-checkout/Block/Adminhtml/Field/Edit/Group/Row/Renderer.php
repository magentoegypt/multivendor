<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout for Magento 2
 */

namespace Amasty\Checkout\Block\Adminhtml\Field\Edit\Group\Row;

use Amasty\Checkout\Api\Data\PlaceholderInterface;
use Amasty\Checkout\Model\PlaceholderRepository;
use Amasty\CheckoutCore\Block\Adminhtml\Field\Edit\Group\Row\Renderer as CheckoutRender;
use Amasty\CheckoutCore\Model\Field;
use Magento\Framework\App\ObjectManager;

class Renderer extends CheckoutRender
{
    /**
     * @var string
     */
    protected $_template = 'Amasty_Checkout::widget/form/renderer/row.phtml';

    /**
     * @var string[]
     */
    private $allowedFrontendInputs = ['text', 'multiline', 'textarea'];

    /**
     * @param int $attributeId
     * @param int $storeId
     *
     * @return PlaceholderInterface|null
     */
    public function getPlaceholder(int $attributeId, int $storeId): ?PlaceholderInterface
    {
        $objectManager = ObjectManager::getInstance();
        $placeholderRepository = $objectManager->create(PlaceholderRepository::class);

        return $placeholderRepository->getByAttributeIdAndStoreId($attributeId, $storeId);
    }

    public function isPlaceholderForbidden(Field $field): bool
    {
        if ($field->getData('attribute_code') === 'region') {
            return true;
        }

        if (!in_array($field->getData('frontend_input'), $this->allowedFrontendInputs, true)) {
            return true;
        }

        return false;
    }
}
