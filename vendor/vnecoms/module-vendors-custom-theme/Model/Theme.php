<?php
namespace Vnecoms\VendorsCustomTheme\Model;

use Magento\Framework\App\ObjectManager;

class Theme extends \Magento\Framework\Model\AbstractModel
{    
    const STATUS_ENABLE = 1;
    const STATUS_DISABLE = 0;
    
    const ENTITY = 'vendor_theme';
    
    /**
     * Model event prefix
     *
     * @var string
     */
    protected $_eventPrefix = 'vendor_theme';
    
    /**
     * Name of the event object
     *
     * @var string
     */
    protected $_eventObject = 'vendor_theme';
    
    /**
     * @var \Vnecoms\VendorsCustomTheme\Model\ResourceModel\Theme\Config\Collection
     */
    protected $_configCollection;
    
    /**
     * @var \Vnecoms\VendorsCustomTheme\Model\ResourceModel\Theme\Config\Collection
     */
    protected $configCollectionByVendor;
    
    /**
     * System configuration structure
     *
     * @var \Magento\Config\Model\Config\Structure
     */
    protected $_configStructure;
    
    /**
     * TransactionFactory
     *
     * @var \Magento\Framework\DB\TransactionFactory
     */
    protected $_transactionFactory;
    
    /**
     * Config data factory
     *
     * @var \Magento\Framework\App\Config\ValueFactory
     */
    protected $_configValueFactory;
    
    /**
     * @var \Vnecoms\VendorsCustomTheme\Model\Theme\ConfigFactory
     */
    protected $_themeConfigFactory;
    
    /**
     * @var \Magento\Theme\Model\Theme
     */
    protected $baseTheme;
    
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Vnecoms\VendorsCustomTheme\Model\ResourceModel\Theme');
    }
    
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Vnecoms\VendorsCustomTheme\Model\ResourceModel\Theme $resource,
        \Vnecoms\VendorsCustomTheme\Model\ResourceModel\Theme\Collection $resourceCollection,
        \Magento\Config\Model\Config\Structure $configStructure,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Magento\Framework\App\Config\ValueFactory $configValueFactory,
        \Vnecoms\VendorsCustomTheme\Model\Theme\ConfigFactory $themeConfigFactory,
        array $data = []
    ) {
        $this->_configStructure = $configStructure;
        $this->_transactionFactory = $transactionFactory;
        $this->_configValueFactory = $configValueFactory;
        $this->_themeConfigFactory = $themeConfigFactory;
        
        parent::__construct($context, $registry, $resource, $resourceCollection);
    }
    
    /**
     * Is enabled theme
     * 
     * @return boolean
     */
    public function isEnabled(){
        return $this->getStatus() == self::STATUS_ENABLE;
    }
    
    /**
     * Get all sections
     * 
     * @return array
     */
    public function getAllSections(){
        if(!$this->getId()) return [];
        
        return $this->getResource()->getAllSections($this);
    }
    
    /**
     * Add section
     * 
     * @param string $sectionName
     */
    public function addSection($sectionName){
        $this->getResource()->addSection($this, $sectionName);
    }

    /**
     * @return \Vnecoms\VendorsCustomTheme\Model\ResourceModel\Theme\Config\Collection
     */
    public function getAllConfigs(){
        $configs = [];
        foreach($this->getConfigCollection() as $config){
            $configs[$config->getPath()] = $config->getValue();
        }
        
        return $configs;
    }
    
    /**
     * Get all config object of current theme
     * 
     * @return multitype:unknown
     */
    public function getAllConfigObjects(){
        $configs = [];
        foreach($this->getConfigCollection() as $config){
            $configs[$config->getPath()] = $config;
        }
    
        return $configs;
    }
    
    /**
     * Get Config Collection
     * 
     * @return \Vnecoms\VendorsCustomTheme\Model\ResourceModel\Theme\Config\Collection
     */
    public function getConfigCollection(){
        if(!$this->_configCollection){
            $om = ObjectManager::getInstance();
            $this->_configCollection = $om->create('Vnecoms\VendorsCustomTheme\Model\ResourceModel\Theme\Config\Collection');
            $this->_configCollection->addFieldToFilter('theme_id', $this->getId())
                ->addFieldToFilter('vendor_id', 0);
        }
        
        return $this->_configCollection;
    }
    
    /**
     * @param int|\Vnecoms\Vendors\Model\Vendor $vendorId
     * @return \Vnecoms\VendorsCustomTheme\Model\ResourceModel\Theme\Config\Collection
     */
    public function getAllConfigsByVendor($vendorId){
        $configs = [];
        foreach($this->getConfigCollectionByVendor($vendorId) as $config){
            $configs[$config->getPath()] = $config->getValue();
        }
    
        return $configs;
    }
    
    /**
     * Get all config object of current theme
     *
     * @param int|\Vnecoms\Vendors\Model\Vendor $vendorId
     * @return multitype:unknown
     */
    public function getAllConfigObjectsByVendor($vendorId){
        $configs = [];
        foreach($this->getConfigCollectionByVendor($vendorId) as $config){
            $configs[$config->getPath()] = $config;
        }
        
        return $configs;
    }
    
    /**
     * @param int|\Vnecoms\Vendors\Model\Vendor $vendorId
     * @return \Vnecoms\VendorsCustomTheme\Model\ResourceModel\Theme\Config\Collection
     */
    public function getConfigCollectionByVendor($vendorId){
        if(!$this->configCollectionByVendor){
            if($vendorId instanceof \Vnecoms\Vendors\Model\Vendor){
                $vendorId = $vendorId->getId();
            }
            
            $om = ObjectManager::getInstance();
            $this->configCollectionByVendor = $om->create('Vnecoms\VendorsCustomTheme\Model\ResourceModel\Theme\Config\Collection');
            $this->configCollectionByVendor->addFieldToFilter('theme_id', $this->getId())
                ->addFieldToFilter('vendor_id', $vendorId);
        }
        return $this->configCollectionByVendor;
    }
    
    public function save(){
        $groups = $this->getGroups();
        parent::save();
        if (empty($groups)) {
            return $this;
        }
        
        $deleteTransaction = $this->_transactionFactory->create();
        /* @var $deleteTransaction \Magento\Framework\DB\Transaction */
        $saveTransaction = $this->_transactionFactory->create();
        /* @var $saveTransaction \Magento\Framework\DB\Transaction */
        
        // Extends for old config data
        $extraOldGroups = [];
        
        $oldConfig = $this->getAllConfigObjects();
        
        foreach ($groups as $sectionId => $sectionData) {
            foreach($sectionData as $groupId => $groupData){
                $this->_processGroup(
                    $groupId,
                    $groupData,
                    $sectionData,
                    $sectionId,
                    $oldConfig,
                    $saveTransaction,
                    $deleteTransaction
                );
            }
        }
        
        try {
            $deleteTransaction->delete();
            $saveTransaction->save();

        } catch (\Exception $e) {
            throw $e;
        }
        return parent::save();
    }
    
    /**
     * 
     * @param unknown $groupId
     * @param array $groupData
     * @param array $groups
     * @param unknown $sectionPath
     * @param array $oldConfig
     * @param \Magento\Framework\DB\Transaction $saveTransaction
     * @param \Magento\Framework\DB\Transaction $deleteTransaction
     */
    protected function _processGroup(
        $groupId,
        array $groupData,
        array $groups,
        $sectionPath,
        array &$oldConfig,
        \Magento\Framework\DB\Transaction $saveTransaction,
        \Magento\Framework\DB\Transaction $deleteTransaction
    ) {
        $groupPath = $sectionPath . '/' . $groupId;

        /**
         *
         * Map field names if they were cloned
        */
        /** @var $group \Magento\Config\Model\Config\Structure\Element\Group */
        $group = $this->_configStructure->getElement($groupPath);
    
        // set value for group field entry by fieldname
        // use extra memory
        $fieldsetData = [];
        if (isset($groupData['fields'])) {
            if ($group->shouldCloneFields()) {
                $cloneModel = $group->getCloneModel();
                $mappedFields = [];
    
                /** @var $field \Magento\Config\Model\Config\Structure\Element\Field */
                foreach ($group->getChildren() as $field) {
                    foreach ($cloneModel->getPrefixes() as $prefix) {
                        $mappedFields[$prefix['field'] . $field->getId()] = $field->getId();
                    }
                }
            }
            foreach ($groupData['fields'] as $fieldId => $fieldData) {
                $fieldsetData[$fieldId] = is_array(
                    $fieldData
                ) && isset(
                    $fieldData['value']
                ) ? $fieldData['value'] : null;
            }
    
            foreach ($groupData['fields'] as $fieldId => $fieldData) {
                $originalFieldId = $fieldId;
                if ($group->shouldCloneFields() && isset($mappedFields[$fieldId])) {
                    $originalFieldId = $mappedFields[$fieldId];
                }
                /** @var $field \Magento\Config\Model\Config\Structure\Element\Field */
                $field = $this->_configStructure->getElement($groupPath . '/' . $originalFieldId);
    
                /** @var \Magento\Framework\App\Config\ValueInterface $backendModel */
                $backendModel = $field->hasBackendModel() ? $field
                    ->getBackendModel() : $this
                    ->_configValueFactory
                    ->create();
    
                $data = [
                    'field' => $fieldId,
                    'groups' => $groups,
                    'group_id' => $group->getId(),
                    'scope' => 'default',
                    'scope_id' => 0,
                    'scope_code' => '',
                    'field_config' => $field->getData(),
                    'fieldset_data' => $fieldsetData
                ];
                $backendModel->addData($data);
        
                if (false == isset($fieldData['value'])) {
                    $fieldData['value'] = null;
                }
    
                $path = $field->getGroupPath() . '/' . $fieldId;
                /**
                 * Look for custom defined field path
                 */
                if ($field && $field->getConfigPath()) {
                    $path = $field->getConfigPath();
                }
    
                $inherit = !empty($fieldData['inherit']);
                
                $backendModel->setPath($path)->setValue($fieldData['value']);

                $backendModel->beforeSave();
                $saveData = [
                    'theme_id' => $this->getThemeId(),
                    'path' => $path,
                    'value' => $backendModel->getValue(),
                ];

                $themeConfig = $this->_themeConfigFactory->create();
                $themeConfig->setData($saveData);

                if (isset($oldConfig[$path])) {
                    $themeConfig->setConfigId($oldConfig[$path]->getId());
    
                    /**
                     * Delete config data if inherit
                    */
                    if (!$inherit) {
                        $saveTransaction->addObject($themeConfig);
                    } else {
                        $deleteTransaction->addObject($themeConfig);
                    }
                } elseif (!$inherit) {
                    $saveTransaction->addObject($themeConfig);
                }
            }
        }
    
        if (isset($groupData['groups'])) {
            foreach ($groupData['groups'] as $subGroupId => $subGroupData) {
                $this->_processGroup(
                    $subGroupId,
                    $subGroupData,
                    $groups,
                    $groupPath,
                    $oldConfig,
                    $saveTransaction,
                    $deleteTransaction
                );
            }
        }
    }
    
    /**
     * Get base theme
     * 
     * @return \Magento\Theme\Model\Theme
     */
    public function getBaseTheme(){
        if(!$this->baseTheme){
            $this->baseTheme = ObjectManager::getInstance()->create('Magento\Theme\Model\Theme');
            $this->baseTheme->load($this->getBaseThemeId());
        }
        
        return $this->baseTheme;
    }
}
