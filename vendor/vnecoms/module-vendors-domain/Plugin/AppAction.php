<?php

namespace Vnecoms\VendorsDomain\Plugin;

class AppAction
{    
    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;
    /**
     * @var \Vnecoms\Vendors\Helper\Data
     */
    protected $helper;

    /**
     * @var \Vnecoms\Vendors\Model\Session
     */
    protected $session;
    
    /**
     * @var \Magento\Framework\App\ActionFlag
     */
    protected $actionFlag;
    
    /**
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Vnecoms\VendorsDomain\Helper\Data $helper
     * @param \Vnecoms\Vendors\Model\Session $session
     * @param \Magento\Framework\App\ActionFlag $actionFlag
     */
    public function __construct(
        \Magento\Framework\Registry $coreRegistry,
        \Vnecoms\Vendors\Helper\Data $helper,
        \Vnecoms\Vendors\Model\Session $session,
        \Magento\Framework\App\ActionFlag $actionFlag
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->helper       = $helper;
        $this->session      = $session;
        $this->actionFlag   = $actionFlag;
    }
    
    /**
     * (non-PHPdoc)
     * @see \Magento\Framework\Url::getBaseUrl()
     */
    public function aroundDispatch(
        \Vnecoms\Vendors\App\AbstractAction $subject,
        \Closure $proceed,
        \Magento\Framework\App\RequestInterface $request
    ){
        $redirectUrl = $this->helper->getUrl('account/login');
        if (
            $this->helper->moduleEnabled() &&
            $this->coreRegistry->registry('current_vendor_domain') &&
            !$this->session->isLoggedIn()
        ) {
            $redirectUrl = $this->helper->getUrl('account/login');
            if ($request->getParam('isAjax')) {
                $body = [
                    'ajaxExpired'   => 1,
                    'ajaxRedirect'  => $redirectUrl,
                ];
                $this->session->setBeforeAuthUrl($this->helper->getUrl('dashboard'));
                return $subject->getResponse()->setBody(json_encode($body));
            } else {
                $this->session->setBeforeAuthUrl($this->helper->getUrl('dashboard'));
                $this->session->setIsUrlNotice($this->actionFlag->get('', \Vnecoms\Vendors\App\AbstractAction::FLAG_IS_URLS_CHECKED));
                $subject->getResponse()->setRedirect($redirectUrl);
                $this->actionFlag->set('', 'no-dispatch', true);
                return $subject->getResponse();
            }
        }
        
        return $proceed($request);
    }
    
}
