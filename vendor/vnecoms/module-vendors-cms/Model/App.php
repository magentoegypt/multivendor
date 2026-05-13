<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Model;

use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * App Model.
 *
 * @method string getTitle()
 * @method \Vnecoms\VendorsCms\Model\App setTitle(string $value)
 * @method \Vnecoms\VendorsCms\Model\App setParameters(string $value)
 * @method int getSortOrder()
 * @method \Vnecoms\VendorsCms\Model\App setSortOrder(int $value)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class App extends \Magento\Framework\Model\AbstractModel
{
    const SPECIFIC_ENTITIES = 'specific';

    const ALL_ENTITIES = 'all';

    const DEFAULT_LAYOUT_HANDLE = 'vendor_page';

    const PRODUCT_LAYOUT_HANDLE = 'catalog_product_view';

    const SINGLE_PRODUCT_LAYOUT_HANLDE = 'catalog_product_view_id_{{ID}}';

    const PRODUCT_TYPE_LAYOUT_HANDLE = 'catalog_product_view_type_{{TYPE}}';

    const ANCHOR_CATEGORY_LAYOUT_HANDLE = 'catalog_category_view_type_layered';

    const NOTANCHOR_CATEGORY_LAYOUT_HANDLE = 'catalog_category_view_type_default';

    const SINGLE_CATEGORY_LAYOUT_HANDLE = 'catalog_category_view_id_{{ID}}';

    /**
     * Prefix of model events names.
     *
     * @var string
     */
    protected $_eventPrefix = 'vendorscms_app';

    /**
     * @var int
     */
    const STATUS_ENABLED = 1;

    /**
     * @var int
     */
    const STATUS_DISABLED = 0;

    /**
     * @var \Magento\Framework\App\Cache\TypeListInterface
     */
    protected $_cacheTypeList;

    /**
     * @var array
     */
    protected $_layoutHandles = [];

    /**
     * @var array
     */
    protected $_specificEntitiesLayoutHandles = [];

    /**
     * @var string[]
     */
    protected $_relatedCacheTypes;

    /**
     * @var \Magento\Framework\Escaper
     */
    protected $_escaper;

    /**
     * @var \Magento\Framework\Math\Random
     */
    protected $mathRandom;

    /**
     * @var \Magento\Framework\Filesystem\Directory\ReadInterface
     */
    protected $_directory;

    /**
     * @var \Magento\Widget\Helper\Conditions
     */
    protected $conditionsHelper;

    /**
     * @var array
     */
    protected $appConfigArray = [];

    /**
     * @var \Magento\Framework\View\FileSystem
     */
    protected $_viewFileSystem;

    protected $_productType;

    /**
     * @var \Vnecoms\VendorsCms\Model\PageType\Config
     */
    protected $_config;

    protected $pageGroupConfig;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Escaper $escaper,
        \Magento\Framework\View\FileSystem $viewFileSystem,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Catalog\Model\Product\Type $productType,
        \Magento\Widget\Model\Config\Reader $reader,
        \Magento\Widget\Model\NamespaceResolver $namespaceResolver,
        \Magento\Framework\Math\Random $mathRandom,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Widget\Helper\Conditions $conditionsHelper,
        \Vnecoms\VendorsCms\Model\PageType\Config $pageTypeConfig,
        \Vnecoms\VendorsCms\Model\App\Page\Group\Config $pageGroupConfig,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $relatedCacheTypes = [],
        array $data = []
    ) {
        $this->_escaper = $escaper;
        $this->_cacheTypeList = $cacheTypeList;
        $this->_relatedCacheTypes = $relatedCacheTypes;
        $this->_productType = $productType;
        $this->_reader = $reader;
        $this->mathRandom = $mathRandom;
        $this->conditionsHelper = $conditionsHelper;
        $this->_directory = $filesystem->getDirectoryRead(DirectoryList::ROOT);
        $this->_viewFileSystem = $viewFileSystem;
        $this->_config = $pageTypeConfig;
        $this->pageGroupConfig = $pageGroupConfig;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);

        $this->_layoutHandles = [
            'anchor_categories' => self::ANCHOR_CATEGORY_LAYOUT_HANDLE,
            'notanchor_categories' => self::NOTANCHOR_CATEGORY_LAYOUT_HANDLE,
            'all_products' => self::PRODUCT_LAYOUT_HANDLE,
            'all_pages' => self::DEFAULT_LAYOUT_HANDLE,
        ];
        $this->_specificEntitiesLayoutHandles = [
            'anchor_categories' => self::SINGLE_CATEGORY_LAYOUT_HANDLE,
            'notanchor_categories' => self::SINGLE_CATEGORY_LAYOUT_HANDLE,
            'all_products' => self::SINGLE_PRODUCT_LAYOUT_HANLDE,
        ];
        foreach (array_keys($this->_productType->getTypes()) as $typeId) {
            $layoutHandle = str_replace('{{TYPE}}', $typeId, self::PRODUCT_TYPE_LAYOUT_HANDLE);
            $this->_layoutHandles[$typeId] = $layoutHandle;
            $this->_specificEntitiesLayoutHandles[$typeId] = self::SINGLE_PRODUCT_LAYOUT_HANLDE;
        }
    }

    /**
     * Internal Constructor.
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('Vnecoms\VendorsCms\Model\ResourceModel\App');
    }

    /**
     * Prepare page's statuses.
     * Available event cms_page_get_available_statuses to customize statuses.
     *
     * @return array
     */
    public function getAvailableStatuses()
    {
        return [self::STATUS_ENABLED => __('Enabled'), self::STATUS_DISABLED => __('Disabled')];
    }

    /**
     * Validate widget instance data.
     *
     * @return \Magento\Framework\Phrase|bool
     */
    public function validate()
    {
        if ($this->isCompleteToCreate()) {
            return true;
        }

        return __('We cannot create the application because it is missing required information.');
    }

    /**
     * Check if app has required data (other data depends on it).
     *
     * @return bool
     */
    public function isCompleteToCreate()
    {
        return $this->getType();
    }

    /**
     * Getter.
     * If not set return default.
     *
     * @return string
     */
    public function getArea()
    {
        //TODO Shouldn't we get "area" from theme model which we can load using "theme_id"?
        if (!$this->_getData('area')) {
            return \Magento\Framework\View\DesignInterface::DEFAULT_AREA;
        }

        return $this->_getData('area');
    }

    /**
     * Return widget instance code.  If not set, derive value from type (namespace\class).
     *
     * @return string
     */
    public function getCode()
    {
        $code = $this->_getData('code');
        if ($code == null) {
            $code = $this->getReference('type', $this->getType(), 'code');
            $this->setData('code', $code);
        }

        return $code;
    }

    /**
     * Sets the value this widget instance code.
     * The widget code is the 'id' attribute in the widget node.
     * 'code' is used in Magento\Widget\Model\Widget->getWidgetsArray when the array of widgets is created.
     *
     * @param string $code
     *
     * @return $this
     */
    public function setCode($code)
    {
        $this->setData('code', $code);

        return $this;
    }

    /**
     * Setter
     * Prepare widget type.
     *
     * @param string $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->setData('type', $type);

        return $this;
    }

    /**
     * Getter
     * Prepare widget type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->_getData('type');
    }

    public function setInstanceType($type)
    {
        $this->setData('instance_type', $type);

        return $this;
    }

    public function getInstanceType()
    {
        return $this->_getData('instance_type');
    }

    /**
     * Getter
     * Unserialize if serialized string setted.
     *
     * @return array
     */
    public function getParameters()
    {
        if (is_string($this->getData('parameters'))) {
            return unserialize($this->getData('parameters'));
        } elseif (null === $this->getData('parameters')) {
            return [];
        }

        return is_array($this->getData('parameters')) ? $this->getData('parameters') : [];
    }

    /**
     * Get the widget reference (code or namespace\class name) for the passed in type or code.
     *
     * @param string $matchParam
     * @param string $value
     * @param string $requestedParam
     *
     * @return string|null
     */
    public function getReference($matchParam, $value, $requestedParam)
    {
        $reference = null;
        $appsArr = $this->getAppsConfigArray();
        foreach ($appsArr as $app) {
            if ($app[$matchParam] === $value) {
                $reference = $app[$requestedParam];
                break;
            }
        }

        return $reference;
    }

    /**
     * @param array $filters
     *
     * @return array
     */
    public function getAppsConfigArray($filters = [])
    {
        if (empty($this->appConfigArray)) {
            $result = [];
            foreach ($this->getAppsConfig($filters) as $code => $app) {
                $result[$app['name']] = [
                    'name' => __((string) $app['name']),
                    'code' => $code,
                    'type' => $app['@']['type'],
                    'description' => __((string) $app['description']),
                ];
            }
            usort($result, [$this, 'sortApps']);
            $this->appConfigArray = $result;
        }

        return $this->appConfigArray;
    }

    /**
     * Return widget XML configuration as \Magento\Framework\DataObject and makes some data preparations.
     *
     * @param string $type Widget type
     *
     * @return \Magento\Framework\DataObject
     */
    public function getAppConfigAsObject($type)
    {
        $widget = $this->getWidgetByClassType($type);

        $object = new \Magento\Framework\DataObject();
        if ($widget === null) {
            return $object;
        }
        $widget = $this->getAsCanonicalArray($widget);

        // Save all nodes to object data
        $object->setType($type);
        $object->setData($widget);

        // Correct widget parameters and convert its data to objects
        $newParams = $this->prepareWidgetParameters($object);
        $object->setData('parameters', $newParams);

        return $object;
    }

    /**
     * Return widget config based on its class type.
     *
     * @param string $type Widget type
     *
     * @return null|array
     *
     * @api
     */
    public function getAppByClassType($type)
    {
        return \Magento\Framework\App\ObjectManager::getInstance()
            ->get('Vnecoms\VendorsCms\Model\App\Config')->getAppByClassType($type);
    }

    /**
     * Return widget XML configuration as \Magento\Framework\DataObject and makes some data preparations.
     *
     * @param string $type Widget type
     *
     * @return \Magento\Framework\DataObject
     */
    public function getConfigAsObject($type)
    {
        $app = $this->getAppByClassType($type);

        $object = new \Magento\Framework\DataObject();
        if ($app === null) {
            return $object;
        }
        $app = $this->getAsCanonicalArray($app);

        // Save all nodes to object data
        $object->setType($type);
        $object->setData($app);

        // Correct widget parameters and convert its data to objects
        $newParams = $this->prepareParameters($object);
        $object->setData('parameters', $newParams);

        return $object;
    }

    /**
     * Remove attributes from widget array and emulate work of \Magento\Framework\Simplexml\Element::asCanonicalArray.
     *
     * @param array $inputArray
     *
     * @return array
     */
    protected function getAsCanonicalArray($inputArray)
    {
        if (array_key_exists('@', $inputArray)) {
            unset($inputArray['@']);
        }
        foreach ($inputArray as $key => $value) {
            if (!is_array($value)) {
                continue;
            }
            $inputArray[$key] = $this->getAsCanonicalArray($value);
        }

        return $inputArray;
    }

    /**
     * Prepare widget parameters.
     *
     * @param \Magento\Framework\DataObject $object
     *
     * @return array
     */
    protected function prepareParameters(\Magento\Framework\DataObject $object)
    {
        $params = $object->getData('parameters');
        $newParams = [];
        if (is_array($params)) {
            $sortOrder = 0;
            foreach ($params as $key => $data) {
                if (is_array($data)) {
                    $data = $this->prepareDropDownValues($data, $key, $sortOrder);
                    $data = $this->prepareHelperBlock($data);

                    $newParams[$key] = new \Magento\Framework\DataObject($data);
                    ++$sortOrder;
                }
            }
        }
        uasort($newParams, [$this, 'sortParameters']);

        return $newParams;
    }

    /**
     * Prepare drop-down values.
     *
     * @param array  $data
     * @param string $key
     * @param int    $sortOrder
     *
     * @return array
     */
    protected function prepareDropDownValues(array $data, $key, $sortOrder)
    {
        $data['key'] = $key;
        $data['sort_order'] = isset($data['sort_order']) ? (int) $data['sort_order'] : $sortOrder;

        $values = [];
        if (isset($data['values']) && is_array($data['values'])) {
            foreach ($data['values'] as $value) {
                if (isset($value['label']) && isset($value['value'])) {
                    $values[] = $value;
                }
            }
        }
        $data['values'] = $values;

        return $data;
    }

    /**
     * Prepare helper block.
     *
     * @param array $data
     *
     * @return array
     */
    protected function prepareHelperBlock(array $data)
    {
        if (isset($data['helper_block'])) {
            $helper = new \Magento\Framework\DataObject();
            if (isset($data['helper_block']['data']) && is_array($data['helper_block']['data'])) {
                $helper->addData($data['helper_block']['data']);
            }
            if (isset($data['helper_block']['type'])) {
                $helper->setType($data['helper_block']['type']);
            }
            $data['helper_block'] = $helper;
        }

        return $data;
    }

    /**
     * Get apps config from xml file.
     *
     * @param array $filters
     *
     * @return array
     */
    public function getAppsConfig($filters = [])
    {
        $apps = \Magento\Framework\App\ObjectManager::getInstance()
            ->get('Vnecoms\VendorsCms\Model\App\Config')->getApps();
        $result = $apps;

        // filter apps by params
        if (is_array($filters) && count($filters) > 0) {
            foreach ($apps as $code => $app) {
                try {
                    foreach ($filters as $field => $value) {
                        if (!isset($app[$field]) || (string) $app[$field] != $value) {
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
     * Retrieve option array of app types
     * syntax $value => label
     * for select options.
     *
     * @param string $value
     *
     * @return array
     */
    public function getAppsConfigOptionArray($value = 'code')
    {
        $apps = [];
        $appArr = $this->getAppsConfigArray();
        foreach ($appArr as $app) {
            $apps[] = ['value' => $app[$value], 'label' => $app['name']];
        }

        return $apps;
    }

    /**
     * Get vendor id
     *
     * @return int
     */
    public function getVendorId()
    {
        return $this->getData('vendor_id');
    }

    public function setVendorId($id)
    {
        return $this->setData('vendor_id', $id);
    }

    /**
     * Get list of containers that widget is limited to be in.
     *
     * @return array
     */
    public function getSupportedContainers()
    {
        $containers = [];

        return $containers;
    }

    public function beforeSave()
    {
        $pageGroupIds = [];
        $tmpPageGroups = [];
        $pageGroups = $this->getData('page_groups');
        if ($pageGroups) {
            // var_dump($pageGroups);die();
            foreach ($pageGroups as $pageGroup) {
                if (isset($pageGroup[$pageGroup['page_group']])) {
                    $pageGroupData = $pageGroup[$pageGroup['page_group']];
                    if ($pageGroupData['option_id']) {
                        $pageGroupIds[] = $pageGroupData['option_id'];
                    }
                    if (in_array($pageGroup['page_group'], ['pages', 'pages_layout'])) {
                        $layoutHandle = $pageGroupData['layout_handle'];
                    } else {
                        $layoutHandle = $this->_layoutHandles[$pageGroup['page_group']];
                    }
                    if (!isset($pageGroupData['template'])) {
                        $pageGroupData['template'] = '';
                    }
                    $tmpPageGroup = [
                        'option_id' => $pageGroupData['option_id'],
                        'page_group' => $pageGroup['page_group'],
                        'layout_handle' => $layoutHandle,
                        'page_for' => $pageGroupData['page_for'],
                        'block_reference' => $pageGroupData['block'],
                        'entities' => '',
                        'layout_handle_updates' => [$layoutHandle],
                        'template' => $pageGroupData['template'] ? $pageGroupData['template'] : '',
                    ];
                    if ($pageGroupData['page_for'] == self::SPECIFIC_ENTITIES) {
                        $layoutHandleUpdates = [];
                        foreach (explode(',', $pageGroupData['entities']) as $entity) {
                            $layoutHandleUpdates[] = str_replace(
                                '{{ID}}',
                                $entity,
                                $this->_specificEntitiesLayoutHandles[$pageGroup['page_group']]
                            );
                        }
                        $tmpPageGroup['entities'] = $pageGroupData['entities'];
                        $tmpPageGroup['layout_handle_updates'] = $layoutHandleUpdates;
                    }
                    $tmpPageGroups[] = $tmpPageGroup;
                }
            }
        }

        $parameters = $this->getData('parameters');
        if (is_array($parameters)) {
            if (array_key_exists('show_pager', $parameters) && !array_key_exists('page_var_name', $parameters)) {
                $parameters['page_var_name'] = 'p'.$this->mathRandom->getRandomString(
                    5,
                    \Magento\Framework\Math\Random::CHARS_LOWERS
                );
            }
            $this->setData('parameters', serialize($parameters));
        }
        //var_dump($tmpPageGroups);
      //  var_dump($pageGroupIds);

     //   die();
        $this->setData('page_groups', $tmpPageGroups);
        $this->setData('page_group_ids', $pageGroupIds);

        return parent::beforeSave(); // TODO: Change the autogenerated stub
    }

    /**
     * Generate layout update xml.
     *
     * @param string $container
     * @param string $templatePath
     *
     * @return string
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function generateLayoutUpdateXml($container, $templatePath = '')
    {
        //        $templateFilename = $this->_viewFileSystem->getTemplateFileName(
//            $templatePath,
//            [
//                'area' => $this->getArea(),
//                'themeId' => $this->getThemeId(),
//                'module' => \Magento\Framework\View\Element\AbstractBlock::extractModuleName($this->getType())
//            ]
//        );
//        if (!$this->getId() && !$this->isCompleteToCreate() || $templatePath && !is_readable($templateFilename)) {
//            return '';
//        }
        $parameters = $this->getParameters();
        $xml = '<body><referenceContainer name="'.$container.'">';
        $template = '';
        if (isset($parameters['template'])) {
            unset($parameters['template']);
        }
//        if ($templatePath) {
//            $template = ' template="' . $templatePath . '"';
//        }

        $hash = $this->mathRandom->getUniqueHash();
        $xml .= '<block class="'.$this->getType().'" name="'.$hash.'"'.$template.'>';
        foreach ($parameters as $name => $value) {
            if ($name == 'conditions') {
                $name = 'conditions_encoded';
                $value = $this->conditionsHelper->encode($value);
            } elseif (is_array($value)) {
                $value = implode(',', $value);
            }
            if ($name && strlen((string) $value)) {
                $xml .= '<action method="setData">'.
                    '<argument name="name" xsi:type="string">'.
                    $name.
                    '</argument>'.
                    '<argument name="value" xsi:type="string">'.
                    $this->_escaper->escapeHtml(
                        $value
                    ).'</argument>'.'</action>';
            }
        }
        $xml .= '</block></referenceContainer></body>';

        return $xml;
    }

    /**
     * Invalidate related cache types.
     *
     * @return $this
     */
    protected function _invalidateCache()
    {
        if (count($this->_relatedCacheTypes)) {
            $this->_cacheTypeList->invalidate($this->_relatedCacheTypes);
        }

        return $this;
    }

    /**
     * Encode string to valid HTML id element, based on base64 encoding.
     *
     * @param string $string
     *
     * @return string
     */
    protected function idEncode($string)
    {
        return strtr(base64_encode($string), '+/=', ':_-');
    }

    /**
     * User-defined apps sorting by Name.
     *
     * @param array $firstElement
     * @param array $secondElement
     *
     * @return bool
     */
    protected function sortApps($firstElement, $secondElement)
    {
        return strcmp($firstElement['name'], $secondElement['name']);
    }

    /**
     * App parameters sort callback.
     *
     * @param \Magento\Framework\DataObject $firstElement
     * @param \Magento\Framework\DataObject $secondElement
     *
     * @return int
     */
    protected function sortParameters($firstElement, $secondElement)
    {
        $aOrder = (int) $firstElement->getData('sort_order');
        $bOrder = (int) $secondElement->getData('sort_order');

        return $aOrder < $bOrder ? -1 : ($aOrder > $bOrder ? 1 : 0);
    }

    /**
     * @return array
     */
    public function getAllHandlesByDefault()
    {
        $result = [];
//        foreach ($this->pageGroupConfig->getPageGroups() as $group) {
//            $groupObject = \Magento\Framework\App\ObjectManager::getInstance()
//                ->get($group['class']);
//            $result = array_merge($result, $groupObject->getLayoutHandles());
//        }
        foreach ($this->_config->getPageTypes() as $key => $pageType) {
            $result[] = $key;
        }
        $result = array_merge($result, [
            self::PRODUCT_LAYOUT_HANDLE,
            '1column',
            '2columns-left',
            '2columns-right',
            '3columns',
        ]);

        return $result;
    }

    public function afterSave()
    {
        $this->_cacheTypeList->cleanType('layout', 'block_html');
        return parent::afterSave(); // TODO: Change the autogenerated stub
    }
}
