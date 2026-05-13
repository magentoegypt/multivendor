<?php
/**
 * Page layout config model.
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Model\App;

class Config
{
    /**
     * Available apps.
     *
     * @var array
     */
    protected $_apps = null;

    /**
     * Data storage.
     *
     * @var \Magento\Framework\Config\DataInterface
     */
    protected $_dataStorage;

    /**
     * Constructor.
     *
     * @param \Magento\Framework\Config\DataInterface $dataStorage
     */
    public function __construct(\Magento\Framework\Config\DataInterface $dataStorage)
    {
        $this->_dataStorage = $dataStorage;
    }

    /**
     * Initialize page types list.
     *
     * @param array $filters
     *
     * @return $this
     */
    protected function _initApps($filters = [])
    {
        $apps = $this->_dataStorage->get(null);
        $result = $apps;

        // filter widgets by params
        if (is_array($filters) && count($filters) > 0) {
            foreach ($apps as $code => $app) {
                try {
                    foreach ($filters as $field => $value) {
                        if (!isset($apps[$field]) || (string) $apps[$field] != $value) {
                            throw new \Exception();
                        }
                    }
                } catch (\Exception $e) {
                    unset($result[$code]);
                    continue;
                }
            }
        }

        return $result;
    }

    /**
     * @return Config
     */
    public function getApps()
    {
        return $this->_initApps();
    }

    /**
     * @param string $type
     *
     * @return null|array
     */
    public function getAppByClassType($type)
    {
        $apps = $this->getApps();
        foreach ($apps as $app) {
            if (isset($app['@'])) {
                if (isset($app['@']['type'])) {
                    if ($type === $app['@']['type']) {
                        return $app;
                    }
                }
            }
        }

        return;
    }
}
