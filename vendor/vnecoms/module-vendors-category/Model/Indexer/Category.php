<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCategory\Model\Indexer;

use Magento\Framework\Indexer\CacheContext;

class Category implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface
{
    /**
     * @var \Vnecoms\VendorsCategory\Model\Indexer\Action\FullFactory
     */
    protected $fullActionFactory;

    /**
     * @var \Vnecoms\VendorsCategory\Model\Indexer\Action\RowsFactory
     */
    protected $rowsActionFactory;

    /**
     * @var \Magento\Framework\Indexer\IndexerRegistry
     */
    protected $indexerRegistry;

    /**
     * @var \Magento\Framework\Indexer\CacheContext
     */
    private $cacheContext;

    /**
     * @param Action\FullFactory                         $fullActionFactory
     * @param Action\RowsFactory                         $rowsActionFactory
     * @param \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry
     */
    public function __construct(
        Action\FullFactory $fullActionFactory,
        Action\RowsFactory $rowsActionFactory,
        \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry
    ) {
        $this->fullActionFactory = $fullActionFactory;
        $this->rowsActionFactory = $rowsActionFactory;
        $this->indexerRegistry = $indexerRegistry;
    }

    /**
     * Execute materialization on ids entities.
     *
     * @param int[] $ids
     */
    public function execute($ids)
    {
        $indexer = $this->indexerRegistry->get('ves_category_url');
        if ($indexer->isInvalid()) {
            return;
        }

        /** @var Flat\Action\Rows $action */
        $action = $this->rowsActionFactory->create();
        if ($indexer->isWorking()) {
            $action->reindex($ids, true);
        }
        $action->reindex($ids);
        $this->getCacheContext()->registerEntities(\Vnecoms\VendorsCategory\Model\Category::CACHE_TAG, $ids);
    }

    /**
     * Execute full indexation.
     */
    public function executeFull()
    {
        $this->fullActionFactory->create()->reindexAll();
        $this->getCacheContext()->registerTags([\Vnecoms\VendorsCategory\Model\Category::CACHE_TAG]);
    }

    /**
     * Execute partial indexation by ID list.
     *
     * @param int[] $ids
     */
    public function executeList(array $ids)
    {
        $this->execute($ids);
    }

    /**
     * Execute partial indexation by ID.
     *
     * @param int $id
     */
    public function executeRow($id)
    {
        $this->execute([$id]);
    }

    /**
     * Get cache context.
     *
     * @return \Magento\Framework\Indexer\CacheContext
     *
     * @deprecated
     */
    protected function getCacheContext()
    {
        if (!($this->cacheContext instanceof CacheContext)) {
            return \Magento\Framework\App\ObjectManager::getInstance()->get(CacheContext::class);
        } else {
            return $this->cacheContext;
        }
    }
}
