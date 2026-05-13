<?php
/**
 * Copyright © 2018 Vnecoms. All rights reserved.
 * See LICENSE.txt for license details.
 */


namespace Vnecoms\Quotation\Model\Quote\Pdf\Config;

/**
 * Class with class map capability
 *
 * ...
 */
class SchemaLocator extends \Magento\Sales\Model\Order\Pdf\Config\SchemaLocator implements \Magento\Framework\Config\SchemaLocatorInterface
{
    /**
     * Path to corresponding XSD file with validation rules for merged configs
     *
     * @var string
     */
    private $_schema;

    /**
     * Path to corresponding XSD file with validation rules for individual configs
     *
     * @var string
     */
    private $_schemaFile;

    /**
     * @param \Magento\Framework\Module\Dir\Reader $moduleReader
     */
    public function __construct(
        \Magento\Framework\Module\Dir\Reader $moduleReader
    ){
        $dir = $moduleReader->getModuleDir(\Magento\Framework\Module\Dir::MODULE_ETC_DIR, 'Vnecoms_Quotation');
        $this->_schema = $dir . '/quote_pdf.xsd';
        $this->_schemaFile = $dir . '/quote_pdf_file.xsd';
    }

    /**
     * Get path to merged config schema
     */
    public function getSchema()
    {
        return $this->_schema;
    }
    /**
     * Get path to per file validation schema
     */
    public function getPerFileSchema()
    {
        return $this->_schemaFile;
    }
}
