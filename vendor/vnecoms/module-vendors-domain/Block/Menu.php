<?php
namespace Vnecoms\VendorsDomain\Block;

class Menu extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Vnecoms\VendorsPage\Helper\Data
     */
    protected $vendorPageHelper;
    
    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;
    
    /**
     * Top links
     *
     * @var array
     */
    protected $_links = [];
    
    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Vnecoms\VendorsPage\Helper\Data $vendorPageHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Vnecoms\VendorsPage\Helper\Data $vendorPageHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->vendorPageHelper = $vendorPageHelper;
        $this->coreRegistry = $registry;
    }
    
    
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
     * Get current vendor
     * @return \Vnecoms\Vendors\Model\Vendor
     */
    public function getVendor(){
        return $this->coreRegistry->registry('vendor');
    }
    
    /**
     * @param string $label
     * @param string|null $title
     * @param string|null $url
     * @param int|null $sortOrder
     * @return $this
     */
    public function addLink($label, $title = null, $url = '', $position = 0, $vendorHelperUrl = true)
    {
        if (empty($title)) {
            $title = $label;
        }
        $vendor = $this->getVendor();
        $url = $vendorHelperUrl?$this->vendorPageHelper->getUrl($vendor, $url):$this->getUrl($url);
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
