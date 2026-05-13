<?php
/**
* Copyright 2017 Vnecoms. All rights reserved.
* See LICENSE.txt for license details.
*/

namespace Vnecoms\BannerManager\Model;

use Vnecoms\BannerManager\Api\Data\ItemInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Vnecoms\BannerManager\Api\Data\ItemInterfaceFactory;
use Magento\Framework\EntityManager\EntityManager;

/**
 * Class ItemRegistry
 * @package Vnecoms\BannerManager\Model
 */
class ItemRegistry
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var ItemInterfaceFactory
     */
    private $itemDataFactory;

    /**
     * @var array
     */
    private $itemRegistry = [];

    /**
     * @param EntityManager $entityManager,
     * @param ItemInterfaceFactory $itemDataFactory
     */
    public function __construct(
        EntityManager $entityManager,
        ItemInterfaceFactory $itemDataFactory
    ) {
        $this->entityManager = $entityManager;
        $this->itemDataFactory = $itemDataFactory;
    }

    /**
     * Retrieve Item from registry by ID
     *
     * @param int $itemId
     * @return Item
     * @throws NoSuchEntityException
     */
    public function retrieve($itemId)
    {
        if (!isset($this->itemRegistry[$itemId])) {
            /** @var ItemInterface $item */
            $item = $this->itemDataFactory->create();
            $this->entityManager->load($item, $itemId);
            if (!$item->getId()) {
                throw NoSuchEntityException::singleField('itemId', $itemId);
            } else {
                $this->itemRegistry[$itemId] = $item;
            }
        }
        return $this->itemRegistry[$itemId];
    }

    /**
     * Remove instance of the Item from registry by ID
     *
     * @param int $itemId
     * @return void
     */
    public function remove($itemId)
    {
        if (isset($this->itemRegistry[$itemId])) {
            unset($this->itemRegistry[$itemId]);
        }
    }

    /**
     * Replace existing Item with a new one
     *
     * @param ItemInterface $item
     * @return $this
     */
    public function push(ItemInterface $item)
    {
        $this->itemRegistry[$item->getId()] = $item;
        return $this;
    }
}
