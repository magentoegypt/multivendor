<?php
/**
 * Copyright © 2017 Vnecoms. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vnecoms\VendorsSellerList\Model\Source;

class SellerFields
{
    const NAME = 'name';

    const ID = 'entity_id';

    const SELLER_ID = 'vendor_id';

    const IMAGE = 'image';

    const REVIEWS_RATING = 'reviews_rating';

    const SHORT_DESCRIPTION = 'short_description';

    const DESCRIPTION = 'description';

    const URL = 'url';

    const SELLER_URL = 'seller_url';

    const SELLER_ITEM_URL = 'seller_item_urls';

    const SELLER_PRODUCT_COUNT = 'product_counts';

    /**
     *
     * @return array
     */
    public function toOptionArray()
    {
        $this->options = [
//            ['value' => self::NAME, 'label' => __('Seller Name')],
            ['value' => self::SELLER_ID, 'label' => __('Seller ID')],
            ['value' => self::SELLER_URL, 'label' => __('Seller URL')],
            ['value' => self::IMAGE, 'label' => __('Seller Image')],
            ['value' => self::SELLER_ITEM_URL, 'label' => __('Seller Item Url')],
            ['value' => self::SELLER_PRODUCT_COUNT, 'label' => __('Seller Product Number')],
            /*['value' => self::REVIEWS_RATING, 'label' => __('Reviews Rating')],
            ['value' => self::SHORT_DESCRIPTION, 'label' => __('Short Description')],
            ['value' => self::DESCRIPTION, 'label' => __('Description')],*/
        ];
   
        return $this->options;
    }
}
