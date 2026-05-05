<?php 
namespace MagentoEgypt\PortoExtend\Plugin\Model;

class Block
{
    protected $_coreRegistry;
    protected $_blockFactory;
    
    public function __construct(
        \Vnecoms\VendorsCms\Model\BlockFactory $blockFactory,
        \Magento\Framework\Registry $coreRegistry
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->_blockFactory = $blockFactory;
    }

	public function afterGetContent($subject, $content)
	{
		$theme = $this->_coreRegistry->registry('vendor_custom_theme');
        if(empty($theme)) {
            $blockId = $subject->getBlockId();
            $vendorId = $this->getVendorId();
            $block = $this->_blockFactory->create();
            $block->setVendorId($vendorId)->load($blockId);
            $content = $block->isActive() ? $block->getContent() : '';
        }
		return $content;
	}

	/**
     * @return mixed
     */
    public function getVendorId()
    {
        if (!$this->_coreRegistry->registry('vendor')) {
            return false;
        }
        return $this->_coreRegistry->registry('vendor')->getId();
    }
}