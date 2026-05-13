<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\Field\Form;

use Amasty\CheckoutCore\Cache\InvalidateCheckoutCache;
use Amasty\CheckoutCore\Model\Field\Form\Processor\ProcessorInterface;
use Amasty\CheckoutCore\Model\ResourceModel\Field as FieldResource;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class SaveHandler
{
    /**
     * @var FieldResource
     */
    private $fieldResource;

    /**
     * @var InvalidateCheckoutCache
     */
    private $invalidateCheckoutCache;

    /**
     * @var ProcessorInterface[]
     */
    private $processors;

    /**
     * @param FieldResource $fieldResource
     * @param InvalidateCheckoutCache $invalidateCheckoutCache
     * @param ProcessorInterface[] $processors
     */
    public function __construct(
        FieldResource $fieldResource,
        InvalidateCheckoutCache $invalidateCheckoutCache,
        array $processors = []
    ) {
        $this->fieldResource = $fieldResource;
        $this->invalidateCheckoutCache = $invalidateCheckoutCache;
        $this->processors = $processors;
    }

    /**
     * @param array<int, array> $fields
     * @param int $storeId
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    public function execute(array $fields, int $storeId): void
    {
        if (empty($this->processors) || empty($fields)) {
            return;
        }

        $this->validateProcessors();

        try {
            $this->fieldResource->beginTransaction();

            foreach ($this->processors as $processor) {
                $fields = $processor->process($fields, $storeId);
            }

            $this->fieldResource->getConnection()->commit();
            $this->invalidateCheckoutCache->execute();
        } catch (\Exception $e) {
            $this->fieldResource->rollBack();
            throw $e;
        }
    }

    /**
     * @throws \InvalidArgumentException
     * @SuppressWarnings(PHPMD.MissingImport)
     */
    private function validateProcessors(): void
    {
        foreach ($this->processors as $processor) {
            if (!$processor instanceof ProcessorInterface) {
                throw new \InvalidArgumentException(
                    sprintf('Processor must implement %s', ProcessorInterface::class)
                );
            }
        }
    }
}
