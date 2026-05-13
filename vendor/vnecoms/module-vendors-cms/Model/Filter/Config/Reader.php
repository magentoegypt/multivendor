<?php

namespace Vnecoms\VendorsCms\Model\Filter\Config;

use Magento\Framework\Config\Reader\Filesystem;
use Magento\Framework\Config\FileResolverInterface;
use Magento\Framework\Config\ValidationStateInterface;

/**
 * Validate, read and convert elastic search indices analysis files.
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class Reader extends Filesystem
{
    /**
     * Reader supported filename.
     *
     * @var string
     */
    const FILENAME = 'cms_filter.xml';

    /**
     * List of attributes by XPath used as ids during the file merge process.
     *
     * @var array
     */
    protected $_idAttributes = ['/config/cms_filter' => 'id'];

    /**
     * {@inheritdoc}
     */
    public function __construct(
        FileResolverInterface $fileResolver,
        Converter $converter,
        SchemaLocator $schemaLocator,
        ValidationStateInterface $validationState,
        $fileName = self::FILENAME,
        $idAttributes = [],
        $domDocumentClass = 'Magento\Framework\Config\Dom',
        $defaultScope = 'global'
    ) {
        parent::__construct(
            $fileResolver,
            $converter,
            $schemaLocator,
            $validationState,
            $fileName,
            $idAttributes,
            $domDocumentClass,
            $defaultScope
        );
    }
}
