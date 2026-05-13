<?php
/**
 * Created by PhpStorm.
 * User: nvhai
 * Date: 12/28/2016
 * Time: 10:03 AM
 */
namespace Vnecoms\RMA\Model\System\Config\Source;

class Block implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options array
     *
     * @var array
     */
    protected $_options = null;
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        if ($this->_options === null) {
            $this->_options = [];
            $om = \Magento\Framework\App\ObjectManager::getInstance();
            $blockCollection = $om->create('Magento\Cms\Model\Block')->getCollection();
            foreach ($blockCollection as $group) {
                $this->_options[] = ['label' => $group->getTitle(), 'value' => $group->getBlockId()];
            }
        }
        return $this->_options;
    }

    /**
     * Retrieve option array
     *
     * @return array
     */
    public function getOptionArray()
    {
        $_options = [];
        foreach ($this->toOptionArray() as $option) {
            $_options[$option['value']] = $option['label'];
        }
        return $_options;
    }
}
