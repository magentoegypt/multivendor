<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Section extends AbstractDb
{
    public const TABLE = 'magentoegypt_hubapp_home_section';

    protected function _construct(): void
    {
        $this->_init(self::TABLE, 'section_id');
    }
}
