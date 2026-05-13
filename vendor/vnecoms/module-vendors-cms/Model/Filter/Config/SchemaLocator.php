<?php

namespace Vnecoms\VendorsCms\Model\Filter\Config;

use Magento\Framework\Module\Dir;
use Magento\Framework\Config\SchemaLocatorInterface;

/**
 * Locate schema validation for elasticsearch analysis configuration files.
 */
class SchemaLocator implements SchemaLocatorInterface
{
    /**
     * Path to corresponding XSD file with validation rules for both individual and merged configs.
     *
     * @var string
     */
    private $schema;

    /**
     * Constructor.
     *
     * @param \Magento\Framework\Module\Dir\Reader $moduleReader Module directory reader.
     */
    public function __construct(\Magento\Framework\Module\Dir\Reader $moduleReader)
    {
        $moduleDirectory = $moduleReader->getModuleDir(Dir::MODULE_ETC_DIR, 'Vnecoms_VendorsCms');
        $this->schema = $moduleDirectory.'/cms_filter.xsd';
    }

    /**
     * {@inheritdoc}
     */
    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * {@inheritdoc}
     */
    public function getPerFileSchema()
    {
        return $this->schema;
    }
}
