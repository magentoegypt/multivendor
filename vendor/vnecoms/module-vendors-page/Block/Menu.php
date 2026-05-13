<?php
namespace Vnecoms\VendorsPage\Block;

class Menu extends \Magento\Framework\View\Element\Template
{
    /**
     * Top links
     *
     * @var array
     */
    protected $_links = [];
    
    
    /**
     * Return new link position in list
     *
     * @param int $position
     * @return int
     */
    protected function _getNewPosition($position = 0)
    {
        if (intval($position) > 0) {
            while (isset($this->_links[$position])) {
                $position++;
            }
        } else {
            $position = 0;
            foreach ($this->_links as $k => $v) {
                $position = $k;
            }
            $position += 10;
        }
        return $position;
    }
    
    
    /**
     * @param string $label
     * @param string|null $title
     * @param string|null $url
     * @param int|null $sortOrder
     * @return $this
     */
    public function addLink($label, $title = null, $url = '', $position = 0)
    {
        if (empty($title)) {
            $title = $label;
        }
        $this->_links[$this->_getNewPosition($position)] = [
            'label' => __($label),
            'title' => __($title),
            'url' => $url,
            'sort_order'=>$position
        ];
        return $this;
    }
    
    /**
     * {@inheritdoc}
     */
    protected function _beforeToHtml()
    {
        // TODO - Moved to Beta 2, no breadcrumbs displaying in Beta 1
        ksort($this->_links);
        $this->assign('links', $this->_links);
        return parent::_beforeToHtml();
    }
}
