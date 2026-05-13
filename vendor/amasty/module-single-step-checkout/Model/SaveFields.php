<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout for Magento 2
 */

namespace Amasty\Checkout\Model;

use Amasty\Checkout\Api\Data\PlaceholderInterface;
use Amasty\CheckoutCore\Model\Field;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;

class SaveFields
{
    /**
     * @var PlaceholderRepository
     */
    private $placeholderRepository;

    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    public function __construct(
        PlaceholderRepository $placeholderRepository,
        DataObjectHelper $dataObjectHelper
    ) {
        $this->placeholderRepository = $placeholderRepository;
        $this->dataObjectHelper = $dataObjectHelper;
    }

    /**
     * @param array $fields
     * @param int $storeId
     *
     * @throws CouldNotDeleteException
     * @throws CouldNotSaveException
     */
    public function saveFields(array $fields, int $storeId): void
    {
        foreach ($fields as $attributeId => $fieldData) {
            $placeholderEntity = $this->placeholderRepository->getById(
                (int)$fieldData[PlaceholderInterface::PLACEHOLDER_ID]
            );

            if (($storeId != Field::DEFAULT_STORE_ID && empty($fieldData['use_default']))
                || $storeId == Field::DEFAULT_STORE_ID) {
                unset($fieldData[PlaceholderInterface::PLACEHOLDER_ID], $fieldData['id']);
                $fieldData[PlaceholderInterface::ATTRIBUTE_ID] = $attributeId;
                $fieldData[PlaceholderInterface::STORE_ID] = $storeId;

                $this->dataObjectHelper->populateWithArray(
                    $placeholderEntity,
                    $fieldData,
                    PlaceholderInterface::class
                );

                $this->placeholderRepository->save($placeholderEntity);
            } else {
                $this->placeholderRepository->delete($placeholderEntity);
            }
        }
    }
}
