<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsProductImportExport\Model\Import\Source;

/**
 * Zip import adapter.
 */
class Zip extends Csv
{
    /**
     * @param string $file
     * @param \Magento\Framework\Filesystem\Directory\Write $directory
     * @param string $options
     */
    public function __construct(
        $file,
        \Magento\Framework\Filesystem\Directory\Write $directory,
        $options
    ) {
        $zip = new \Magento\Framework\Archive\Zip();
        $file = $zip->unpack(
            $file,
            preg_replace('/\.zip$/i', '.csv', $file)
        );
        parent::__construct($file, $directory, $options);
    }
}
