<?php

namespace Vnecoms\VendorsProductImportExport\Model\Import\Source;

use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Magento\Framework\Exception\LocalizedException;

/**
 * Xlsx import adapter
 */
class Xlsx extends \Magento\ImportExport\Model\Import\AbstractSource
{
    /**
     * @var \Box\Spout\Reader\XLSX\RowIterator
     */
    protected $reader;
    
    /**
     * @var \Box\Spout\Reader\XLSX\Reader
     */
    protected $file;
    
    /**
     * @param string $file
     * @param \Magento\Framework\Filesystem\Directory\Write $directory
     * @param string $sheetName
     * @throws \LogicException
     */
    public function __construct(
        $file,
        \Magento\Framework\Filesystem\Directory\Write $directory,
        $sheetName = 'data'
    ) {
        register_shutdown_function([$this, 'destruct']);
        
        try {
            $reader = ReaderEntityFactory::createXLSXReader();
            $reader->open($file);
            $this->file = $reader;
            
            $header = [];
            $sourceData = [];
            foreach ($reader->getSheetIterator() as $sheet) {
                // only read data from "summary" sheet
                if ($sheet->getName() != $sheetName) continue;
                $this->reader = $sheet->getRowIterator();
                $this->reader->rewind();
                break;
            }
            if(!$this->reader) throw new LocalizedException(__("The sheet name '%1' does not exist.", $sheetName));
        } catch (\Magento\Framework\Exception\FileSystemException $e) {
            throw new \LogicException("Unable to open file: '{$file}'");
        }
        $this->_getNextRow();
        parent::__construct($this->_getNextRow());
    }
    
    /**
     * Close file handle
     *
     * @return void
     */
    public function destruct()
    {
        if (is_object($this->file)) {
            $this->file->close();
        }
    }
    
    /**
     * Read next line from CSV-file
     *
     * @return array|bool
     */
    protected function _getNextRow()
    {
        $row = $this->reader->current();
        $this->reader->next();
        $result = $row->toArray();
        return $result;
    }
    
    /**
     * Rewind the \Iterator to the first element (\Iterator interface)
     *
     * @return void
     */
    public function rewind()
    {
        $this->reader->rewind();
        $this->reader->next();
        $this->reader->next();
        $this->reader->next();
        $this->reader->next();
        parent::rewind();
    }
}
