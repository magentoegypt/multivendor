<?php

namespace Vnecoms\VendorsConfig\Api\Data;

interface ConfigInterface
{
    /**#@+
     * Constants defined for keys of the data array. Identical to the name of the getter in snake case
     */
    const ID                = 'config_id';
    const VENDOR_ID         = 'vendor_id';
    const PATH             = 'path';
    const VALUE              = 'value';
    const STORE_ID         = 'store_id';

    /**#@-*/

    /**
     * Get id
     *
     * @return int|null
     */
    public function getConfigId();

    /**
     * Set vendor id
     *
     * @param int $id
     * @return \Vnecoms\VendorsConfig\Api\Data\ConfigInterface
     */
    public function setConfigId($id);

    /**
     * Get message
     *
     * @return string
     */
    public function getVendorId();

    /**
     * Set transaction amount
     *
     * @param string $vendorId
     * @return \Vnecoms\VendorsConfig\Api\Data\ConfigInterface
     */
    public function setVendorId($vendorId);

    /**
     * Get additional info
     *
     * @return string
     */
    public function getPath();

    /**
     * Set additional info
     *
     * @param string $path
     * @return \Vnecoms\VendorsConfig\Api\Data\ConfigInterface
     */
    public function setPath($path);

    /**
     * Get is read
     *
     * @return string
     */
    public function getValue();

    /**
     * Set is read
     *
     * @param string $value
     * @return \Vnecoms\VendorsConfig\Api\Data\ConfigInterface
     */
    public function setValue($value);

    /**
     * Get is reached
     *
     * @return string
     */
    public function getStoreId();

    /**
     * Set store Id
     *
     * @param string $storeId
     * @return \Vnecoms\VendorsConfig\Api\Data\ConfigInterface
     */
    public function setStoreId($storeId);

}
