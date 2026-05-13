<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Model\App\Config;

class Reader extends \Magento\Framework\Config\Reader\Filesystem
{
    /**
     * List of identifier attributes for merging.
     *
     * @var array
     */
    protected $_idAttributes = [
        '/apps/app' => 'id',
        '/apps/app/parameters/parameter' => 'name',
        '/apps/app/parameters/parameter/options/option' => 'name',
        '/apps/app/containers/container' => 'name',
        '/apps/app/containers/container/template' => 'name',
    ];

    /**
     * @param \Magento\Framework\Config\FileResolverInterface    $fileResolver
     * @param Converter                                          $converter
     * @param SchemaLocator                                      $schemaLocator
     * @param \Magento\Framework\Config\ValidationStateInterface $validationState
     * @param string                                             $fileName
     * @param array                                              $idAttributes
     * @param string                                             $domDocumentClass
     * @param string                                             $defaultScope
     */
    public function __construct(
        \Magento\Framework\Config\FileResolverInterface $fileResolver,
        Converter $converter,
        SchemaLocator $schemaLocator,
        \Magento\Framework\Config\ValidationStateInterface $validationState,
        $fileName = 'cms_app.xml',
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

    /**
     * Load configuration file.
     *
     * @param string $file
     *
     * @return array
     */
    public function readFile($file)
    {
        return $this->_readFiles([$file]);
    }
}
