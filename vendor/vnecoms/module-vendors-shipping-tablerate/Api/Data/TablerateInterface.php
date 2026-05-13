<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsShippingTableRate\Api\Data;

/**
 * RMA Reponse interface.
 * @api
 */
interface TablerateInterface
{
    /**#@+
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    const RATE_ID      = 'rate_id';
    const VENDOR_ID         = 'vendor_id';
    const COUNTRY_ID       = 'dest_country_id';
    const REGION_ID       = 'dest_region_id';
    const REGION_NAME       = 'dest_region_name';
    const ZIP       = 'dest_zip';
    const CONDITION_NAME       = 'condition_name';
    const CONDITION_VALUE_FROM       = 'condition_value_from';
    const CONDITION_VALUE_TO       = 'condition_value_to';
    const PRICE       = 'price';
    const DELIVERY_TYPE       = 'delivery_type';

    /**#@-*/

    /**
     * Get ID
     *
     * @return int|null
     */
    public function getId();


    /**
     * Get vendor id
     *
     * @return string|null
     */
    public function getVendorId();

    /**
     * Get dest_country_id
     *
     * @return string|null
     */
    public function getDestCountryId();

    /**
     * Get dest_region_id
     *
     * @return string|null
     */
    public function getDestRegionId();

    /**
     * Get dest_zip
     *
     * @return string|null
     */
    public function getDestZip();


    /**
     * Get condition_name
     *
     * @return string|null
     */
    public function getConditionName();

    /**
     * Get condition_value_from
     *
     * @return string|null
     */
    public function getConditionValueFrom();


    /**
     * Get condition_value_to
     *
     * @return string|null
     */
    public function getConditionValueTo();

    /**
     * Get price
     *
     * @return string|null
     */
    public function getPrice();

    /**
     * Get delivery_type
     *
     * @return string|null
     */
    public function getDeliveryType();

    /**
     * Set ID
     *
     * @param int $id
     * @return \Vnecoms\VendorsShippingTableRate\Api\Data\TablerateInterface
     */
    public function setId($id);


    /**
     * Set vendor Id
     *
     * @param string $vendorId
     * @return \Vnecoms\VendorsShippingTableRate\Api\Data\TablerateInterface
     */
    public function setVendorId($vendorId);

    /**
     * Set dest_country_id
     *
     * @param string $countryId
     * @return \Vnecoms\VendorsShippingTableRate\Api\Data\TablerateInterface
     */
    public function setDestCountryId($countryId);

    /**
     * Set dest_region_id
     *
     * @param string $regionId
     * @return \Vnecoms\VendorsShippingTableRate\Api\Data\TablerateInterface
     */
    public function setDestRegionId($regionId);

    /**
     * Get region name
     *
     * @return string|null
     */
    public function getDestRegionName();

    /**
     * Set region name
     *
     * @param string $regionName
     * @return $this
     */
    public function setDestRegionName($regionName);

    /**
     * Set dest_zip
     *
     * @param string $zipcode
     * @return \Vnecoms\VendorsShippingTableRate\Api\Data\TablerateInterface
     */
    public function setDestZip($zipcode);

    /**
     * Set condition_name
     *
     * @param string $name
     * @return \Vnecoms\VendorsShippingTableRate\Api\Data\TablerateInterface
     */
    public function setConditionName($name);

    /**
     * Set condition_value_from
     *
     * @param string $value
     * @return \Vnecoms\VendorsShippingTableRate\Api\Data\TablerateInterface
     */
    public function setConditionValueFrom($value);

    /**
     * Set condition_value_to
     *
     * @param string $value
     * @return \Vnecoms\VendorsShippingTableRate\Api\Data\TablerateInterface
     */
    public function setConditionValueTo($value);


    /**
     * Set price
     *
     * @param string $value
     * @return \Vnecoms\VendorsShippingTableRate\Api\Data\TablerateInterface
     */
    public function setPrice($value);

    /**
     * Set Delivery Type
     *
     * @param string $value
     * @return \Vnecoms\VendorsShippingTableRate\Api\Data\TablerateInterface
     */
    public function setDeliveryType($value);

}
