<?php
/**
 * Locator for page_types XSD schemas.
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Model\PageType\Config;

use Magento\Framework\Module\Dir;

class SchemaLocator implements \Magento\Framework\Config\SchemaLocatorInterface
{
    /**
     * Path to corresponding XSD file with validation rules for config.
     *
     * @var string
     */
    protected $schema;

    /**
     * @param \Magento\Framework\Module\Dir\Reader $moduleReader
     */
    public function __construct(\Magento\Framework\Module\Dir\Reader $moduleReader)
    {
        $moduleDirectory = $moduleReader->getModuleDir(Dir::MODULE_ETC_DIR, 'Vnecoms_VendorsCms');
        $this->schema = $moduleDirectory.'/cms_page_types.xsd';
    }

    /**
     * Get path to merged config schema.
     *
     * @return string|null
     */
    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * Get path to per file validation schema.
     *
     * @return string|null
     */
    public function getPerFileSchema()
    {
        return $this->schema;
    }
}
