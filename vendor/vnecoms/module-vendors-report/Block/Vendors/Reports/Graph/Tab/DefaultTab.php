<?php
namespace Vnecoms\VendorsReport\Block\Vendors\Reports\Graph\Tab;

use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;

class DefaultTab extends Generic implements TabInterface
{
    /**
     * @var string
     */
    protected $_template = 'Vnecoms_VendorsReport::reports/graph/default_tab.phtml';
    
    /**
     * Prepare content for tab
     *
     * @return \Magento\Framework\Phrase
     * @codeCoverageIgnore
     */
    public function getTabLabel()
    {
        return __($this->getData('tab_label'));
    }
    
    /**
     * Prepare title for tab
     *
     * @return \Magento\Framework\Phrase
     * @codeCoverageIgnore
     */
    public function getTabTitle()
    {
        return __($this->getData('tab_title'));
    }
    
    /**
     * Get the id of graph container
     * @return string
     */
    public function getGraphContainerId()
    {
        return $this->getData('graph_container_id');
    }
    
    /**
     * Returns status flag about this tab can be showed or not
     *
     * @return bool
     * @codeCoverageIgnore
     */
    public function canShowTab()
    {
        return true;
    }
    
    /**
     * Returns status flag about this tab hidden or not
     *
     * @return bool
     * @codeCoverageIgnore
     */
    public function isHidden()
    {
        return false;
    }
}
