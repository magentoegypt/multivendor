<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

// @codingStandardsIgnoreFile

namespace Vnecoms\VendorsCustomTheme\Model\ResourceModel;

/**
 * Cms page mysql resource
 */
class Theme extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('ves_vendor_theme', 'theme_id');
    }
    
    /**
     * Get all section setting of the theme
     * 
     * @param \Vnecoms\VendorsCustomTheme\Model\Theme $theme
     * @return multitype:
     */
    public function getAllSections(\Vnecoms\VendorsCustomTheme\Model\Theme $theme){
        $table = $this->getTable('ves_vendor_theme_config_section');
        $connection = $this->getConnection();
        $select = $connection->select();
        
        $select->from(
            $table,
            ['section']
        )->where('theme_id = :theme_id');
        
        $bind = ['theme_id' => $theme->getId(),];
        
        return $connection->fetchCol($select, $bind);
    }
    
    /**
     * Add section
     * 
     * @param \Vnecoms\VendorsCustomTheme\Model\Theme $theme
     * @param string $sectionName
     */
    public function addSection(\Vnecoms\VendorsCustomTheme\Model\Theme $theme, $sectionName){
        $table = $this->getTable('ves_vendor_theme_config_section');
        $connection = $this->getConnection();
        $select = $connection->select();
        
        $select->from(
            $table,
            ['section']
        )->where('theme_id = :theme_id')
        ->where('section = :section');
        
        $bind = ['theme_id' => $theme->getId(), 'section' => $sectionName];
        
        if($connection->fetchOne($select, $bind)) return;
        
        $connection->insert($table, [
            'theme_id' => $theme->getId(),
            'section' => $sectionName
        ]);
    }
}
