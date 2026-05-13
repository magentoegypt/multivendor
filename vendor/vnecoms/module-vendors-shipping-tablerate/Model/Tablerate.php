<?php

namespace Vnecoms\VendorsShippingTableRate\Model;

use Magento\Framework\Model\AbstractModel;
use Vnecoms\VendorsShippingTableRate\Api\Data\TablerateInterface;

class Tablerate extends AbstractModel implements TablerateInterface
{
    /**
     * Define resource model
     */
    protected function _construct()
    {
        $this->_init('Vnecoms\VendorsShippingTableRate\Model\ResourceModel\Tablerate');
    }

    /**
     * Processing object before save data
     *
     * @return $this
     */
    public function beforeSave()
    {
        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $region= $object_manager->get('\Magento\Directory\Model\Region')->load($this->getDestRegionId());
        $regionName = $region->getId() ? $region->getCode() : "*";
        $this->setData(self::REGION_NAME, $regionName);

        return parent::beforeSave();
    }

    /**
     * Validate Vendor Data
     *
     * @return \Vnecoms\Vendors\Model\Vendor
     */
    public function validate(){
        $errors = [];
        if ('' == trim($this->getVendorId())) {
            $errors[] = __('Please enter vendor id.');
        }

        if ('' == trim($this->getDestRegionId())) {
            $errors[] = __('Please enter region.');
        }

        if ('' == trim($this->getDestCountryId())) {
            $errors[] = __('Please enter country.');
        }

        if ('' == trim($this->getDestZip())) {
            $errors[] = __('Please enter zip code');
        }

        $zip = explode('-', $this->getDestZip());
        if(sizeof($zip) == 2){
            if(!is_numeric($zip[0])){
                $errors[] = __('Zip from must be a number');
            }elseif(!is_numeric($zip[1])){
                $errors[] = __('Zip to must be a number');
            }elseif($zip[0]  >  $zip[1]){
                $errors[] = __('Zip from must be greater than zip to');
            }
        }

        $errors1 = $this->getResource()->validate($this);
        if(is_array($errors1)) $errors = array_merge($errors, $errors1);

        $transport = new \Magento\Framework\DataObject(
            ['errors' => $errors]
        );

        $this->_eventManager->dispatch('table_rate_validate', ['table_rate' => $this, 'transport' => $transport]);

        $errors = $transport->getErrors();

        if (empty($errors)) {
            return true;
        }

        return $errors;
    }

    /**
     * Get ID
     *
     * @return int|null
     */
    public function getId(){
        return $this->getData(self::RATE_ID);
    }


    /**
     * Get vendor id
     *
     * @return string|null
     */
    public function getVendorId(){
        return $this->getData(self::VENDOR_ID);
    }

    /**
     * Get dest_country_id
     *
     * @return string|null
     */
    public function getDestCountryId(){
        return $this->getData(self::COUNTRY_ID);
    }

    /**
     * Get dest_region_id
     *
     * @return string|null
     */
    public function getDestRegionId(){
        return $this->getData(self::REGION_ID);
    }

    /**
     * Get dest_zip
     *
     * @return string|null
     */
    public function getDestZip(){
        return $this->getData(self::ZIP);
    }


    /**
     * Get condition_name
     *
     * @return string|null
     */
    public function getConditionName(){
        return $this->getData(self::CONDITION_NAME);
    }

    /**
     * Get condition_value_from
     *
     * @return string|null
     */
    public function getConditionValueFrom(){
        return $this->getData(self::CONDITION_VALUE_FROM);
    }


    /**
     * Get condition_value_to
     *
     * @return string|null
     */
    public function getConditionValueTo(){
        return $this->getData(self::CONDITION_VALUE_TO);
    }

    /**
     * {@inheritdoc}
     */
    public function getDestRegionName()
    {
        return $this->getData(self::REGION_NAME);
    }
    /**
     * Get price
     *
     * @return string|null
     */
    public function getPrice(){
        return $this->getData(self::PRICE);
    }

    /**
     * Get delivery_type
     *
     * @return string|null
     */
    public function getDeliveryType(){
        return $this->getData(self::DELIVERY_TYPE);
    }

    /**
     * Set ID
     *
     * @param int $id
     * @return \Vnecoms\VendorsShippingTableRate\Api\Data\TablerateInterface
     */
    public function setId($id){
        return $this->setData(self::RATE_ID, $id);
    }


    /**
     * Set vendor Id
     *
     * @param string $vendorId
     * @return \Vnecoms\VendorsShippingTableRate\Api\Data\TablerateInterface
     */
    public function setVendorId($vendorId){
        return $this->setData(self::VENDOR_ID, $vendorId);
    }

    /**
     * Set dest_country_id
     *
     * @param string $countryId
     * @return \Vnecoms\VendorsShippingTableRate\Api\Data\TablerateInterface
     */
    public function setDestCountryId($countryId){
        return $this->setData(self::COUNTRY_ID, $countryId);
    }

    /**
     * Set dest_region_id
     *
     * @param string $regionId
     * @return \Vnecoms\VendorsShippingTableRate\Api\Data\TablerateInterface
     */
    public function setDestRegionId($regionId){
        return $this->setData(self::REGION_ID, $regionId);
    }


    /**
     * Set dest_zip
     *
     * @param string $zipcode
     * @return \Vnecoms\VendorsShippingTableRate\Api\Data\TablerateInterface
     */
    public function setDestZip($zipcode){
        return $this->setData(self::ZIP, $zipcode);
    }

    /**
     * Set condition_name
     *
     * @param string $name
     * @return \Vnecoms\VendorsShippingTableRate\Api\Data\TablerateInterface
     */
    public function setConditionName($name){
        return $this->setData(self::CONDITION_NAME, $name);
    }

    /**
     * Set condition_value_from
     *
     * @param string $value
     * @return \Vnecoms\VendorsShippingTableRate\Api\Data\TablerateInterface
     */
    public function setConditionValueFrom($value){
        return $this->setData(self::CONDITION_VALUE_FROM, $value);
    }

    /**
     * Set condition_value_to
     *
     * @param string $value
     * @return \Vnecoms\VendorsShippingTableRate\Api\Data\TablerateInterface
     */
    public function setConditionValueTo($value){
        return $this->setData(self::CONDITION_VALUE_TO, $value);
    }

    /**
     * Set price
     *
     * @param string $value
     * @return \Vnecoms\VendorsShippingTableRate\Api\Data\TablerateInterface
     */
    public function setPrice($value){
        return $this->setData(self::PRICE, $value);
    }

    /**
     * Set Delivery Type
     *
     * @param string $value
     * @return \Vnecoms\VendorsShippingTableRate\Api\Data\TablerateInterface
     */
    public function setDeliveryType($value){
        return $this->setData(self::DELIVERY_TYPE, $value);
    }

    /**
     * Set region name
     *
     * @param string $regionName
     * @return $this
     */
    public function setDestRegionName($regionName)
    {
        return $this->setData(self::REGION_NAME, $regionName);
    }

}
