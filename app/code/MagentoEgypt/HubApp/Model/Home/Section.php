<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Home;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;
use MagentoEgypt\HubApp\Model\Cache\Tags;
use MagentoEgypt\HubApp\Model\ResourceModel\Section as SectionResource;

/**
 * One row of the app Home (magentoegypt_hubapp_home_section).
 *
 * The identities make a save or delete purge the app's cached Homes on its own:
 * AbstractModel::afterSave() cleans `hm_app_home` from the app cache
 * (cleanModelCache) and dispatches clean_cache_by_tags, which purges the same
 * tags from the built-in FPC or Varnish — where GraphQL GETs are cached. The
 * edited title shows on the next request, with no cache flush by hand.
 */
class Section extends AbstractModel implements IdentityInterface
{
    public const AUDIENCE_ALL = 'all';
    public const AUDIENCE_GUEST = 'guest';
    public const AUDIENCE_CUSTOMER = 'customer';

    public const AUDIENCES = [self::AUDIENCE_ALL, self::AUDIENCE_GUEST, self::AUDIENCE_CUSTOMER];

    /**
     * @var string
     */
    protected $_cacheTag = Tags::APP_HOME;

    /**
     * @var string
     */
    protected $_eventPrefix = 'magentoegypt_hubapp_home_section';

    protected function _construct(): void
    {
        $this->_init(SectionResource::class);
    }

    /**
     * @return string[]
     */
    public function getIdentities(): array
    {
        $tags = [Tags::APP_HOME];
        if ($this->getId()) {
            $tags[] = Tags::homeSection((int) $this->getId());
        }

        return $tags;
    }

    /**
     * AbstractModel cleans the app cache after a save but not after a delete;
     * a deleted section must leave the cached Homes too.
     *
     * @return $this
     */
    public function afterDelete()
    {
        $this->cleanModelCache();

        return parent::afterDelete();
    }
}
