<?php

namespace Vnecoms\VendorsCustomTheme\Controller\Vendors\Index;

use Magento\Framework\Exception\LocalizedException;

class Save extends \Vnecoms\Vendors\App\AbstractAction
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
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;
    
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
        $this->coreRegistry = $context->getCoreRegistry();
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
            $vendorThemeConfigs = $theme->getAllConfigObjectsByVendor($vendor);
            foreach($themeData as $sectionId => $sectionData){
                if(is_array($sectionData)) foreach($sectionData as $groupId => $groupData){
                    if(is_array($groupData)) foreach($groupData as $fieldId => $fieldData){
                        $path = sprintf('%s/%s/%s', $sectionId, $groupId, $fieldId);
                        if(isset($vendorThemeConfigs[$path])){
                            $vendorThemeConfigs[$path]->setValue($fieldData)->save();
                        }else{
                            $themeConfig = $this->themeConfigFactory->create();
                            $themeConfig->setData([
                                'vendor_id' => $vendor->getId(),
                                'theme_id' => $themeId,
                                'path' => $path,
                                'value' => $fieldData
                            ])->save();
                        }
                    }
                }
            }
            $this->messageManager->addSuccess(__("The theme is saved."));
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
