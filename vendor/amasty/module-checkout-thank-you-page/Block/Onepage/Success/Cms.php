<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package One Step Checkout Thank you Page 2 for Magento 2 (System)
 */

namespace Amasty\CheckoutThankYouPage\Block\Onepage\Success;

use Amasty\CheckoutThankYouPage\Model\Config;
use Magento\Cms\Model\BlockRepository;
use Magento\Cms\Model\Template\FilterProvider;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\Context;
use Magento\Store\Model\StoreManagerInterface;

class Cms extends AbstractBlock
{
    /**
     * @var int
     */
    private $blockId;

    /**
     * @var Config
     */
    private $configProvider;

    /**
     * @var FilterProvider
     */
    private $filterProvider;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var BlockRepository
     */
    private $blockRepository;

    public function __construct(
        Context $context,
        FilterProvider $filterProvider,
        StoreManagerInterface $storeManager,
        Config $configProvider,
        BlockRepository $blockRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->configProvider = $configProvider;
        $this->filterProvider = $filterProvider;
        $this->blockRepository = $blockRepository;
        $this->storeManager = $storeManager;
    }

    /**
     * @return int
     */
    public function getBlockId(): int
    {
        if ($this->blockId === null) {
            $this->blockId = $this->configProvider->getSuccessCustomBlockId();
        }

        return (int)$this->blockId;
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    protected function _toHtml(): string
    {
        $blockId = $this->getBlockId();
        $html = '';
        if ($blockId) {
            $storeId = $this->storeManager->getStore()->getId();
            $block = $this->blockRepository->getById($blockId);
            if ($block->isActive()) {
                $html = $this->filterProvider->getBlockFilter()->setStoreId($storeId)->filter($block->getContent());
            }
        }
        return $html;
    }

    /**
     * @return string[]
     * @throws NoSuchEntityException
     */
    public function getCacheKeyInfo(): array
    {
        return array_merge(parent::getCacheKeyInfo(), ['store' . $this->storeManager->getStore()->getId()]);
    }
}
