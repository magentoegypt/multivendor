<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Plugin\MgsBrand;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use MagentoEgypt\HubApp\Api\CacheTagCleanerInterface;
use MagentoEgypt\HubApp\Model\Cache\Tags;

/**
 * After MGS\Brand\Model\Resource\Brand::save() / delete(): purge hm_brand
 * (hmBrands and the TOP_BRANDS section).
 *
 * MGS brands have no cache identities; the admin grid's mass enable / disable
 * saves model by model, so it passes through here too. The subject is typed to
 * the core parent class so this file loads even if MGS_Brand is removed.
 */
class CleanBrandCache
{
    public function __construct(private readonly CacheTagCleanerInterface $tagCleaner)
    {
    }

    /**
     * @param AbstractDb $subject
     * @param mixed $result
     * @return mixed
     */
    public function afterSave(AbstractDb $subject, $result)
    {
        $this->tagCleaner->clean([Tags::BRAND]);

        return $result;
    }

    /**
     * @param AbstractDb $subject
     * @param mixed $result
     * @return mixed
     */
    public function afterDelete(AbstractDb $subject, $result)
    {
        $this->tagCleaner->clean([Tags::BRAND]);

        return $result;
    }
}
