<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Plugin\Checkout\CustomerData;

use Magento\Customer\CustomerData\SectionSourceInterface;

/**
 * Cache Directory data section
 */
class DirectoryDataPlugin
{
    /**
     * @var \Amasty\CheckoutCore\Cache\Type
     */
    private $cacheModel;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    private $serializer;

    /**
     * @var \Amasty\CheckoutCore\Cache\ConditionVariator\StoreId
     */
    private $storeCacheVariator;

    public function __construct(
        \Amasty\CheckoutCore\Cache\Type $cacheModel,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Amasty\CheckoutCore\Cache\ConditionVariator\StoreId $storeCacheVariator
    ) {
        $this->cacheModel = $cacheModel;
        $this->serializer = $serializer;
        $this->storeCacheVariator = $storeCacheVariator;
    }

    /**
     * @param \Magento\Checkout\CustomerData\DirectoryData|SectionSourceInterface $subject
     * @param callable $proceed
     *
     * @return array
     */
    public function aroundGetSectionData(SectionSourceInterface $subject, callable $proceed)
    {
        $data = $this->cacheModel->load($this->getCacheKey());
        if ($data === false) {
            $result = $proceed();
            $this->cacheModel->save($this->serializer->serialize($result), $this->getCacheKey());
        } else {
            $result = $this->serializer->unserialize($data);
        }

        return $result;
    }

    /**
     * @return string
     */
    private function getCacheKey()
    {
        return 'DirectoryData|' . $this->storeCacheVariator->getKeyPart();
    }
}
