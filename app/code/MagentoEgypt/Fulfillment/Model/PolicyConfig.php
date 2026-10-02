<?php
declare(strict_types=1);
namespace MagentoEgypt\Fulfillment\Model;

class PolicyConfig extends \Magento\Framework\App\Config\Value
{
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        private Configuration $configuration,
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    public function beforeSave()
    {
        try {
            $this->configuration->validate((string)$this->getValue());
        } catch (\Throwable $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Invalid fulfillment policy: %1', $e->getMessage()));
        }
        return parent::beforeSave();
    }
}
