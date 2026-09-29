<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\ResourceModel\Section;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use MagentoEgypt\HubApp\Model\Home\Section;
use MagentoEgypt\HubApp\Model\ResourceModel\Section as SectionResource;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'section_id';

    protected function _construct(): void
    {
        $this->_init(Section::class, SectionResource::class);
    }
}
