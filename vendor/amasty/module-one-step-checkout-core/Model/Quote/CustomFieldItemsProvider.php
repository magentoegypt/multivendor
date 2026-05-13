<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\Quote;

use Amasty\CheckoutCore\Api\Data\CustomFieldsConfigInterface;
use Amasty\CheckoutCore\Api\Data\QuoteCustomFieldsInterface;
use Amasty\CheckoutCore\Model\QuoteCustomFields;
use Amasty\CheckoutCore\Model\QuoteCustomFieldsFactory;
use Amasty\CheckoutCore\Model\ResourceModel\QuoteCustomFields as QuoteCustomFieldsResource;
use Amasty\CheckoutCore\Model\ResourceModel\QuoteCustomFields\Collection;
use Amasty\CheckoutCore\Model\ResourceModel\QuoteCustomFields\CollectionFactory;

class CustomFieldItemsProvider
{
    /**
     * @var QuoteCustomFields[]
     */
    private $itemsStorage = [];

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var QuoteCustomFieldsFactory
     */
    private $customFieldsFactory;

    /**
     * @var QuoteCustomFieldsResource
     */
    private $customFieldsResource;

    public function __construct(
        CollectionFactory $collectionFactory,
        QuoteCustomFieldsFactory $customFieldsFactory,
        QuoteCustomFieldsResource $customFieldsResource
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->customFieldsFactory = $customFieldsFactory;
        $this->customFieldsResource = $customFieldsResource;
    }

    /**
     * @param int $quoteId
     *
     * @return QuoteCustomFields[]|QuoteCustomFieldsInterface[]
     */
    public function getItemsByQuoteId(int $quoteId): array
    {
        if (!isset($this->itemsStorage[$quoteId])) {
            $this->itemsStorage[$quoteId] = [];
            /** @var Collection $customFieldsCollection */
            $customFieldsCollection = $this->collectionFactory->create();
            $customFieldsCollection->addFieldByQuoteId($quoteId);

            foreach (CustomFieldsConfigInterface::CUSTOM_FIELDS_ARRAY as $fieldName) {
                /** @var QuoteCustomFields $item */
                $item = $customFieldsCollection->getItemByColumnValue('name', $fieldName);
                if (!$item) {

                    $item = $this->customFieldsFactory->create(
                        ['data' => ['quote_id' => $quoteId, 'name' => $fieldName]]
                    );
                }
                $item->setDataChanges(false);

                $this->itemsStorage[$quoteId][] = $item;
            }
        }

        return $this->itemsStorage[$quoteId];
    }
}
