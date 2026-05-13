<?php
namespace Vnecoms\VendorsProductImportExport\Model\Import\Product\Type;

use Vnecoms\VendorsProductImportExport\Model\ResourceModel\Import\Data\Collection as ImportSource;

interface TypeInterface
{
    public function setSource(ImportSource $source);
    
    public function getSource();
}
