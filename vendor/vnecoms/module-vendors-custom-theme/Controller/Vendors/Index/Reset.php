<?php

namespace Vnecoms\VendorsCustomTheme\Controller\Vendors\Index;

use Magento\Framework\Exception\LocalizedException;

class Reset extends \Vnecoms\Vendors\App\AbstractAction
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_VendorsCustomTheme::theme';
    
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Vnecoms\VendorsCustomTheme\Model\ThemeFactory
     */
    protected $themeFactory;
    
    /**
     * @var \Vnecoms\VendorsCustomTheme\Model\Theme\ConfigFactory
     */
    protected $themeConfigFactory;
    
    /**
     * @param \Vnecoms\Vendors\App\Action\Context $context
     * @param \Vnecoms\VendorsCustomTheme\Model\ThemeFactory $themeFactory
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Vnecoms\VendorsCustomTheme\Model\Theme\ConfigFactory $themeConfigFactory
     */
    public function __construct(
        \Vnecoms\Vendors\App\Action\Context $context,
        \Vnecoms\VendorsCustomTheme\Model\ThemeFactory $themeFactory,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Vnecoms\VendorsCustomTheme\Model\Theme\ConfigFactory $themeConfigFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->themeFactory = $themeFactory;
        $this->themeConfigFactory = $themeConfigFactory;
    }

    /**
     * Edit configuration section
     *
     * @return \Magento\Framework\App\ResponseInterface|void
     */
    public function execute()
    {
        $resultRedirectPage = $this->resultRedirectFactory->create();
        try{
            $themeId = $this->getRequest()->getParam('id');
            $theme = $this->themeFactory->create()->load($themeId);
            if(!$themeId || !$theme->getId()){
                throw new LocalizedException(__("The theme is no longer available"));
            }
            
            $themeData = $this->getRequest()->getParam('theme');
            $vendor = $this->_session->getVendor();
            $themeConfigCollection = $this->themeConfigFactory->create()->getCollection()
                ->addFieldToFilter('vendor_id', $this->_session->getVendor()->getId())
                ->addFieldToFilter('theme_id',$themeId)
                ->addFieldToFilter('path',['like' => 'custom_theme%']);
            
            foreach($themeConfigCollection as $themeConfig){
                $themeConfig->delete();
            }
            $this->messageManager->addSuccess(__("The theme is reset to default."));
            $resultRedirectPage->setUrl($this->getUrl('theme/index/customize',['id' => $themeId]));
            return $resultRedirectPage;
        }catch (LocalizedException $e){
            $this->messageManager->addError($e->getMessage());
            $resultRedirectPage->setUrl($this->getUrl('config/index/edit',['section' => 'custom_theme']));
        }catch(\Exception $e){
            $this->messageManager->addError(__('Something went wrong'));
            $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
            $resultRedirectPage->setUrl($this->getUrl('config/index/edit',['section' => 'custom_theme']));
        }
        return $resultRedirectPage;
    }
}
