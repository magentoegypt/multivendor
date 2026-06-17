<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Model\Template;

/**
 * Cms Template Filter Model.
 */
/**
 * Class Filter.
 */
class Filter extends \Magento\Framework\Filter\Template
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var \Vnecoms\VendorsCms\Model\Filter\Config
     */
    protected $configData;

    /**
     * Store manager.
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var int
     */
    protected $_storeId;

    /**
     * App state.
     *
     * @var \Magento\Framework\App\State
     */
    protected $_appState;

    public function __construct(
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Vnecoms\VendorsCms\Model\Filter\Config $configData,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\State $state,
        array $variables = []
    ) {
        $this->configData = $configData;
        $this->_objectManager = $objectManager;
        $this->_storeManager = $storeManager;
        $this->_appState = $state;
        parent::__construct($string, $variables);
    }

    /**
     * Whether to allow SID in store directive: AUTO.
     *
     * @var bool
     */
    protected $_useSessionInUrl;

    /**
     * Setter whether SID is allowed in store directive.
     *
     * @param bool $flag
     *
     * @return $this
     */
    public function setUseSessionInUrl($flag)
    {
        $this->_useSessionInUrl = (bool) $flag;

        return $this;
    }

    /**
     * @param int $storeId
     *
     * @return $this
     */
    public function setStoreId($storeId)
    {
        $this->_storeId = $storeId;

        return $this;
    }

    /**
     * @return int
     */
    public function getStoreId()
    {
        if (null === $this->_storeId) {
            $this->_storeId = $this->_storeManager->getStore()->getId();
        }

        return $this->_storeId;
    }

    /**
     * {@inheritdoc}
     */
    public function filter($value)
    {
        if (!$value) return '';
        // "depend", "if", and "template" directives should be first
        foreach ([
                     self::CONSTRUCTION_DEPEND_PATTERN => 'dependDirective',
                     self::CONSTRUCTION_IF_PATTERN => 'ifDirective',
                     self::CONSTRUCTION_TEMPLATE_PATTERN => 'templateDirective',
                 ] as $pattern => $directive) {
            if (preg_match_all($pattern, $value, $constructions, PREG_SET_ORDER)) {
                foreach ($constructions as $construction) {
                    $callback = [$this, $directive];
                    if (!is_callable($callback)) {
                        continue;
                    }
                    try {
                        $replacedValue = call_user_func($callback, $construction);
                    } catch (\Exception $e) {
                        throw $e;
                    }
                    $value = str_replace($construction[0], $replacedValue, $value);
                }
            }
        }

        if (preg_match_all(self::CONSTRUCTION_PATTERN, $value, $constructions, PREG_SET_ORDER)) {
            $filters = $this->configData->getFilters();

            foreach ($constructions as $index => $construction) {
                $replacedValue = '';
                if (isset($filters[$construction[1]])) {
                    $handle = $this->_objectManager->get($filters[$construction[1]]['action_class']);
                    $callback = [$handle, $construction[1].'Directive'];
                    if (!is_callable($callback)) {
                        continue;
                    }
                    try {
                        $replacedValue = call_user_func($callback, $construction);
                    } catch (\Exception $e) {
                        throw $e;
                    }
                    $value = str_replace($construction[0], $replacedValue, $value);
                }
            }
        }

        //$value = $this->afterFilter($value);
        return $value;
    }
}
