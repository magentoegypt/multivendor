<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\Field\ConfigManagement\ConfigToField\Processor;

class ProcessorPool
{
    /**
     * @var array<string, ProcessorInterface>
     */
    private $processors;

    /**
     * @param array<string, ProcessorInterface> $processors
     */
    public function __construct(array $processors = [])
    {
        $this->processors = $processors;
    }

    /**
     * @param string $sourceModel
     * @return ProcessorInterface|null
     * @throws \InvalidArgumentException
     * @SuppressWarnings(PHPMD.MissingImport)
     */
    public function get(string $sourceModel): ?ProcessorInterface
    {
        foreach ($this->processors as $processor) {
            if (!$processor instanceof ProcessorInterface) {
                throw new \InvalidArgumentException(
                    sprintf('Processor must implement %s', ProcessorInterface::class)
                );
            }
        }

        return $this->processors[$sourceModel] ?? null;
    }
}
