<?php

namespace Vnecoms\VendorsShippingTableRate\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Tablerate extends AbstractDb
{

    /**
     * Import table rates vendor ID
     *
     * @var int
     */
    protected $_importVendorId     = 0;

    /**
     * Import table rates website ID
     *
     * @var int
     */
    protected $_importWebsiteId     = 0;

    /**
     * Errors in import process
     *
     * @var array
     */
    protected $_importErrors        = array();

    /**
     * Count of imported table rates
     *
     * @var int
     */
    protected $_importedRows        = 0;

    /**
     * Array of unique table rate keys to protect from duplicates
     *
     * @var array
     */
    protected $_importUniqueHash    = array();

    /**
     * Array of countries keyed by iso2 code
     *
     * @var array
     */
    protected $_importIso2Countries;

    /**
     * Array of countries keyed by iso3 code
     *
     * @var array
     */
    protected $_importIso3Countries;

    /**
     * Associative array of countries and regions
     * [country_id][region_code] = region_id
     *
     * @var array
     */
    protected $_importRegions;

    /**
     * Import Table Rate condition name
     *
     * @var string
     */
    protected $_importConditionName;

    /**
     * Array of condition full names
     *
     * @var array
     */
    protected $_conditionFullNames  = array();
    /**
     * Define main table
     */
    protected function _construct()
    {
        $this->_init('ves_vendor_shipping_tablerate', 'rate_id');
    }

    /**
     * validate object
     * (non-PHPdoc)
     */
    public function validate($rate)
    {
        $table = $this->getTable('ves_vendor_shipping_tablerate');
        $connection = $this->getConnection();
        $select = $connection->select();

        $select->from(
            $table,
            "rate_id"
        )->where(
            'vendor_id = :vendor_id'
        )->where(
            'dest_country_id = :dest_country_id'
        )->where(
            'dest_region_id = :dest_region_id'
        )->where(
            'dest_zip = :dest_zip'
        )->where(
            'condition_name = :condition_name'
        )->where(
            'condition_value_from = :condition_value_from'
        )->where(
            'condition_value_to = :condition_value_to'
        );

        $bind = [
            'vendor_id' => $rate->getVendorId(),
            'dest_country_id' => $rate->getDestCountryId(),
            'dest_region_id' => $rate->getDestRegionId(),
            'dest_zip' => $rate->getDestZip(),
            'condition_name' => $rate->getConditionName(),
            'condition_value_from' => $rate->getConditionValueFrom(),
            'condition_value_to' => $rate->getConditionValueTo(),
        ];

        $existRowId = $connection->fetchOne($select,$bind);

        if ($existRowId && ($rate->getId() != $existRowId)) {
            return [__("Duplicate Row")];
        }

        return [];
    }



    public function uploadAndImport($vendorId){
        if(!isset($_FILES) || !isset($_FILES['file_import']) ||
            !isset($_FILES['file_import']['tmp_name'])) return;

        $csvFile = $_FILES['file_import']['tmp_name'];

        $this->_importVendorId      = (int)$vendorId;
        $this->_importUniqueHash    = array();
        $this->_importErrors        = array();
        $this->_importedRows        = 0;

        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $io = $object_manager->get('\Magento\Framework\Filesystem\Driver\File');

        $info   = pathinfo($csvFile);

        $halnen = $io->fileOpen($info['dirname']."/".$info['basename'], 'r');
        // $io->streamStat($info['basename'], 'r');

        // check and skip headers
        $headers = $io->fileGetCsv($halnen);

        if ($headers === false || count($headers) < 8) {
            $io->fileClose($halnen);
            throw new \Exception(__('Invalid Table Rates File Format'));
        }

        //$table = $this->getTable('ves_vendor_shipping_tablerate');
        $connection = $this->getConnection();
        $connection->beginTransaction();
        //$oldData = $this->getRatesByVendorId($vendorId);
        try {
            $rowNumber  = 1;
            $importData = array();

            $this->_loadDirectoryCountries();
            $this->_loadDirectoryRegions();

            while (false !== ($csvLine = $io->fileGetCsv($halnen))) {
                $rowNumber ++;

                if (empty($csvLine)) {
                    continue;
                }

                $row = $this->_getImportRow($csvLine, $rowNumber);

                if ($row !== false) {
                    $importData[] = $row;
                }
                /*
                if (count($importData) == 1) {
                    $this->_saveImportData($importData);
                    $importData = array();
                } */
            }

            if ($this->_importErrors) {
                $error = __('File has not been imported. See the following list of errors: %1', implode(" \n", $this->_importErrors));
                throw new \Magento\Framework\Exception\InputException ($error);
            }

            $this->resetRatesByVendorId($vendorId);
            $this->_saveImportData($importData);

            $io->fileClose($halnen);

        } catch (\Magento\Framework\Exception\InputException $e) {
            $connection->rollback();
            $io->fileClose($halnen);
            throw new \Exception($e->getMessage());
        } catch (\Exception $exception) {
            $connection->rollback();
            $io->fileClose($halnen);
            throw new \Exception(__($exception->getMessage()));
        }
        $connection->commit();

        return $this;
    }

    /**
     * Save import data batch
     *
     * @param array $data
     * @return $this
     */
    protected function _saveImportData(array $data)
    {
        $table = $this->getTable('ves_vendor_shipping_tablerate');
        $connection = $this->getConnection();

        if (!empty($data)) {
            $columns = array('vendor_id', 'dest_country_id', 'dest_region_id','dest_region_name', 'dest_zip',
                'condition_name', 'condition_value_from','condition_value_to', 'price','delivery_type');
            $connection->insertArray($table, $columns, $data);
            $this->_importedRows += count($data);
        }

        return $this;
    }


    /**
     * Validate row for import and return table rate array or false
     * Error will be add to _importErrors array
     *
     * @param array $row
     * @param int $rowNumber
     * @return array|false
     */
    protected function _getImportRow($row, $rowNumber = 0)
    {
        // validate row
        if (count($row) < 8) {
            $this->_importErrors[] = __('Invalid Table Rates format in the Row #%s', $rowNumber);
            return false;
        }

        // strip whitespace from the beginning and end of each row
        foreach ($row as $k => $v) {
            $row[$k] = trim($v);
        }

        // validate country
        if (isset($this->_importIso2Countries[$row[0]])) {
            $countryId = $this->_importIso2Countries[$row[0]];
        } elseif (isset($this->_importIso3Countries[$row[0]])) {
            $countryId = $this->_importIso3Countries[$row[0]];
        } elseif ($row[0] == '*' || $row[0] == '') {
            $countryId = '*';
        } else {
            $this->_importErrors[] = __('Invalid Country "%1" in the Row #%2.', $row[0], $rowNumber);
            return false;
        }

        // validate region
        if ($countryId != '*' && isset($this->_importRegions[$countryId][$row[1]])) {
            $regionId = $this->_importRegions[$countryId][$row[1]];
            $regionName = $row[1];
        } elseif ($row[1] == '*' || $row[1] == '') {
            $regionId = 0;
            $regionName = "*";
        } else {
            $this->_importErrors[] = __('Invalid Region/State "%1" in the Row #%2.', $row[1], $rowNumber);
            return false;
        }

        // detect zip code
        if ($row[2] == '*' || $row[2] == '') {
            $zipCode = '*';
        } else {
            $zipCode = $row[2];
        }

        // validate condition name
        $conditionName = $this->_parseConditionValue($row[3]);
        if ($conditionName === false) {
            $this->_importErrors[] = __('Invalid "%1" in the Row #%2.', $row[3], $rowNumber);
            return false;
        }

        // validate condition value
        $valueFrom = $this->_parseDecimalValue($row[4]);
        if ($valueFrom === false) {
            $this->_importErrors[] = __('Invalid "%1" in the Row #%2.', $row[4], $rowNumber);
            return false;
        }
        $valueTo = $this->_parseDecimalValue($row[5]);
        if ($valueFrom === false) {
            $this->_importErrors[] = __('Invalid"%1" in the Row #%2.', $row[5], $rowNumber);
            return false;
        }

        // validate price
        $price = $this->_parseDecimalValue($row[6]);
        if ($price === false) {
            $this->_importErrors[] = __('Invalid Shipping Price "%1" in the Row #%2.', $row[6], $rowNumber);
            return false;
        }

        $methodTitle = $row[7];

        // protect from duplicate
        $hash = sprintf("%s-%d-%s-%F-%F", $countryId, $regionId, $zipCode,$conditionName,$valueFrom,$valueTo);
        if (isset($this->_importUniqueHash[$hash])) {
            $this->_importErrors[] = __('Duplicate Row #%1 (Country "%2", Region/State "%3", Zip "%4", Condition "%5" Value From "%6" And Value To "%7").',
                $rowNumber, $row[0], $row[1], $zipCode,$conditionName, $valueFrom,$valueTo);
            return false;
        }
        $this->_importUniqueHash[$hash] = true;

        return array(
            $this->_importVendorId,     // vendor_id
            $countryId,                 // dest_country_id
            $regionId,                  // dest_region_id,
            $regionName,                  // dest_region_name,
            $zipCode,                   // dest_zip
            $conditionName ,// condition_name,
            $valueFrom,                 // condition_value
            $valueTo,                   // condition_to
            $price,                     // price
            $methodTitle                // Method Title
        );
    }

    /**
     * load country id
     * @return $this
     */
    protected function _loadDirectoryCountries()
    {
        if (!is_null($this->_importIso2Countries) && !is_null($this->_importIso3Countries)) {
            return $this;
        }

        $this->_importIso2Countries = array();
        $this->_importIso3Countries = array();

        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $collection = $object_manager->get('\Magento\Directory\Model\ResourceModel\Country\Collection');

        foreach ($collection->getData() as $row) {
            $this->_importIso2Countries[$row['iso2_code']] = $row['country_id'];
            $this->_importIso3Countries[$row['iso3_code']] = $row['country_id'];
        }

        return $this;
    }

    /**
     * load region id
     * @return $this
     */
    protected function _loadDirectoryRegions()
    {
        if (!is_null($this->_importRegions)) {
            return $this;
        }

        $this->_importRegions = array();

        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $collection = $object_manager->get('\Magento\Directory\Model\ResourceModel\Region\Collection');

        foreach ($collection->getData() as $row) {
            $this->_importRegions[$row['country_id']][$row['code']] = (int)$row['region_id'];
        }

        return $this;
    }

    /**
     * Parse and validate positive condition value
     *
     * @param string $value
     * @return bool|float
     */
    protected function _parseConditionValue($value)
    {
        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $conditions = $object_manager->get('\Vnecoms\VendorsShippingTableRate\Model\Source\Config\Condition')->getOptionArray();
        $conditions = array_keys($conditions);
        if (!in_array($value,$conditions)) {
            return false;
        }
        return $value;
    }


    /**
     * Parse and validate positive decimal value
     * Return false if value is not decimal or is not positive
     *
     * @param string $value
     * @return bool|float
     */
    protected function _parseDecimalValue($value)
    {
        if (!is_numeric($value)) {
            return false;
        }
        $value = (float)sprintf('%.4F', $value);
        if ($value < 0.0000) {
            return false;
        }
        return $value;
    }

    /**
     * Parse and validate positive decimal value
     *
     * @see self::_parseDecimalValue()
     * @deprecated since 1.4.1.0
     * @param string $value
     * @return bool|float
     */
    protected function _isPositiveDecimalNumber($value)
    {
        return $this->_parseDecimalValue($value);
    }

    /**
     * delete rate by vendor id
     * @param $vendorId
     * @return $this
     */
    public function resetRatesByVendorId($vendorId){
        $table = $this->getTable('ves_vendor_shipping_tablerate');
        $connection = $this->getConnection();
        $connection->query('DELETE FROM `'.
            $table.'` WHERE vendor_id='.$vendorId
        );
        return $this;
    }

    /**
     * @param $vendorId
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getRatesByVendorId($vendorId){
        $connection = $this->getConnection();
        $select = $connection->select()
            ->reset(\Zend_Db_Select::COLUMNS)
            ->columns(['vendor_id', 'dest_country_id', 'dest_region_id','dest_region_name', 'dest_zip',
                'condition_name', 'condition_value_from','condition_value_to', 'price','delivery_type'])
            ->from($this->getMainTable())
            ->where('vendor_id = :vendor_id');
        $bind[':vendor_id']  =$vendorId;
        return $connection->fetchAll($select, $bind);;
    }


    /**
     * @param \Magento\Quote\Model\Quote\Address\RateRequest $request
     * @param \Magento\Framework\DataObject $additionalRequest
     * @param array $items
     * @return array|mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getRate(\Magento\Quote\Model\Quote\Address\RateRequest $request, \Magento\Framework\DataObject $additionalRequest,$items = array())
    {
        $adapter = $this->getConnection();
        $bind = array(
            ':vendor_id' => (int) $additionalRequest->getVendorId(),
            ':country_id' => $request->getDestCountryId(),
            ':region_id' => (int) $request->getDestRegionId()
            // ':postcode' => $request->getDestPostcode()
        );

        $select = $adapter->select()
            ->from($this->getMainTable())
            ->where('vendor_id = :vendor_id')
            ->order(array('dest_country_id DESC', 'dest_region_id DESC', 'dest_zip DESC', 'condition_value_from DESC','condition_value_to DESC'))
        ;

        // Render destination condition
        /*
        $orWhere = '(' . implode(') OR (', array(
                "dest_country_id = :country_id AND dest_region_id = :region_id AND dest_zip = :postcode",
                "dest_country_id = :country_id AND dest_region_id = :region_id AND dest_zip = ''",

                // Handle asterix in dest_zip field
                "dest_country_id = :country_id AND dest_region_id = :region_id AND dest_zip = '*'",
                "dest_country_id = :country_id AND dest_region_id = 0 AND dest_zip = '*'",
                "dest_country_id = '*' AND dest_region_id = :region_id AND dest_zip = '*'",
                "dest_country_id = '*' AND dest_region_id = 0 AND dest_zip = '*'",

                "dest_country_id = :country_id AND dest_region_id = 0 AND dest_zip = ''",
                "dest_country_id = :country_id AND dest_region_id = 0 AND dest_zip = :postcode",
                "dest_country_id = :country_id AND dest_region_id = 0 AND dest_zip = '*'",
            )) . ')';
        */

        $orWhere = '(' . implode(') OR (', array(
                // Handle asterix in dest_zip field
                "dest_country_id = :country_id AND dest_region_id = :region_id",
                "dest_country_id = :country_id AND dest_region_id = 0",
                "dest_country_id = '*' AND dest_region_id = :region_id",
                "dest_country_id = '*' AND dest_region_id = 0",
            )) . ')';

        $select->where($orWhere);


        $bind[':condition_name']  = $additionalRequest->getConditionName();
        $bind[':condition_value'] = $additionalRequest->getConditionValue();
        //$bind[':condition_value'] = ;

        $select->where('condition_name = :condition_name');
        $select->where('condition_value_from <= :condition_value');
        $select->where('condition_value_to >= :condition_value');


        $result = $adapter->fetchAll($select, $bind);
        $result = $this->_processPostCode($result ,$request->getDestPostcode() );
        return $result;
    }

    /**
     * process post code
     * @param $data
     * @param $code
     * @return mixed
     */
    protected function _processPostCode($data , $code){
        $newData = [];
        if(!$data) return [];
        foreach ($data as $rate){
            $zip = explode('-', $rate['dest_zip']);
            if($rate['dest_zip'] == '*' || $rate['dest_zip'] == $code){
                $newData[] = $rate;
            }elseif((sizeof($zip) == 2) && ($code >= $zip[0]) && ($code <= $zip[1])){
                $newData[] = $rate;
            }else{
                $pattern = null;
                $rest = substr($rate['dest_zip'], 0, 1);
                if($rest == "*"){
                    $pattern = str_replace("*","(.*?)",$rate['dest_zip']);
                    $pattern = $pattern."$";
                }
                if(!$pattern){
                    $rest = substr($rate['dest_zip'], -1);
                    if($rest == "*"){
                        $pattern = str_replace("*","(.*?)",$rate['dest_zip']);
                        $pattern = "^".$pattern;
                    }
                }
                if($pattern){
                    if($code && preg_match("/".$pattern."/is",$code)){
                        $newData[] = $rate;
                    }
                }
            }
        }
        foreach ($newData as &$rate){
            if($rate['dest_zip'] == '*')
                $rate['dest_zip'] = '';
        }
        return $newData;
    }
}