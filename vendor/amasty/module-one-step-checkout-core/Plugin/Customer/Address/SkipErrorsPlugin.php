<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Plugin\Customer\Address;

use Amasty\CheckoutCore\Model\Field;
use Magento\Customer\Model\Address\Validator\General;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Store\Model\StoreManagerInterface;

class SkipErrorsPlugin
{
    public const ERROR_MESSAGE = '"%fieldName" is required. Enter and try again.';

    /**
     * @var Field
     */
    private $fieldSingleton;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        Field $fieldSingleton,
        StoreManagerInterface $storeManager
    ) {
        $this->fieldSingleton = $fieldSingleton;
        $this->storeManager = $storeManager;
    }

    /**
     * @param General $validator
     * @param Phrase[] $errors
     * @return Phrase[]
     * @throws NoSuchEntityException
     * @see General::validate
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterValidate(General $validator, array $errors): array
    {
        $fieldConfig = $this->fieldSingleton->getConfig((int) $this->storeManager->getStore()->getId());

        $result = [];
        foreach ($errors as $error) {
            $fieldName = $error->getArguments()['fieldName'] ?? null;
            if ($error->getText() !== self::ERROR_MESSAGE || empty($fieldName)) {
                $result[] = $error;
                continue;
            }

            $field = $fieldConfig[$fieldName] ?? null;

            if ($field && !$field->getIsRequired()) {
                continue;
            }

            $result[] = $error;
        }

        return $result;
    }
}
