<?php
namespace Accept\Payments\Block\Adminhtml;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\View\Helper\SecureHtmlRenderer;

class Field extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @var SecureHtmlRenderer
     */
    protected $secureRenderer;
    
    protected $base_url;

    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        array $data = [],
        ?SecureHtmlRenderer $secureRenderer = null
    ) {
        parent::__construct($context, $data, $secureRenderer);
        $this->secureRenderer = $secureRenderer ?? ObjectManager::getInstance()->get(SecureHtmlRenderer::class);
        $this->base_url = $storeManager->getStore()->getBaseUrl();
    }
}