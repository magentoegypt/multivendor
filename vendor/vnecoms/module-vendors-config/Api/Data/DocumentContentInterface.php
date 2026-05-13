<?php

namespace Vnecoms\VendorsConfig\Api\Data;

/**
 * Documnet Content data interface
 *
 * @api
 */
interface DocumentContentInterface
{
    const BASE64_ENCODED_DATA = 'base64_encoded_data';
    const TYPE                = 'type';
    const NAME                = 'name';

    /**
     * Retrieve media data (base64 encoded content)
     *
     * @return string
     */
    public function getBase64EncodedData();

    /**
     * Set media data (base64 encoded content)
     *
     * @param string $data
     *
     * @return $this
     */
    public function setBase64EncodedData($data);

    /**
     * Retrieve MIME type
     *
     * @return string
     */
    public function getType();

    /**
     * Set MIME type
     *
     * @param string $mimeType
     *
     * @return $this
     */
    public function setType($mimeType);

    /**
     * Retrieve document name
     *
     * @return string
     */
    public function getName();

    /**
     * Set document name
     *
     * @param string $name
     *
     * @return $this
     */
    public function setName($name);
}
