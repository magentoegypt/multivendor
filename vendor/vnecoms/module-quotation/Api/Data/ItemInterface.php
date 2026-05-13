<?php


namespace Vnecoms\Quotation\Api\Data;

interface ItemInterface
{

    const QUOTE_ID = 'quote_id';
    const ITEM_ID = 'item_id';
    const PRODUCT_ID = 'product_id';
    const PARENT_ITEM_ID = 'parent_item_id';
    const SKU = 'sku';
    const NAME = 'name';
    const PRICE = 'price';
    const COMMENT = 'comment';
    const BUY_REQUEST = 'buy_request';
    const DEFAULT_PROPOSAL = 'default_proposal';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const KEY_QTY = 'qty';

    /**
     * Get item_id
     * @return string|null
     */

    public function getItemId();

    /**
     * Set item_id
     * @param string $item_id
     * @return \Vnecoms\Quotation\Api\Data\ItemInterface
     */

    public function setItemId($itemId);

    /**
     * Get quote_id
     * @return string|null
     */

    public function getQuoteId();

    /**
     * Set quote_id
     * @param string $quote_id
     * @return \Vnecoms\Quotation\Api\Data\ItemInterface
     */

    public function setQuoteId($quote_id);

    /**
     * @return int
     */
    public function getProductId();

    /**
     * @param integer $id
     * @return \Vnecoms\Quotation\Api\Data\ItemInterface
     */
    public function setProductId($id);
}
