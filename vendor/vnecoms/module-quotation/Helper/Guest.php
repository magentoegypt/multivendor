<?php

namespace Vnecoms\Quotation\Helper;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;

class Guest
{
    const QUOTATION_GUEST_KEY = 'quotation_guest_is_authorized';
    
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;
    
    /**
     * @var \Vnecoms\Quotation\Model\QuoteRepository
     */
    protected $quoteRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaInterface
     */
    protected $searchCriteriaBuilder;
    
    /**
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory
     * @param \Vnecoms\Quotation\Model\QuoteRepository $quoteRepository
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteriaBuilder
     */
    public function __construct
    (
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory,
        \Vnecoms\Quotation\Model\QuoteRepository $quoteRepository,
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteriaBuilder
    )
    {
        $this->coreRegistry = $coreRegistry;
        $this->storeManager = $storeManager;
        $this->customerSession = $customerSession;
        $this->messageManager = $messageManager;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->quoteRepository = $quoteRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;

    }

    public function loadValidQuote(\Magento\Framework\App\RequestInterface $request)
    {
        if ($this->customerSession->isLoggedIn()) {
            return $this->resultRedirectFactory->create()->setPath('quotation/customer');
        }
        $params = $request->getParams();

        if (empty($params)) {
            return $this->resultRedirectFactory->create()->setPath('quotation/guest');
        }
        try {
            if ($quoteId = $request->getParam('quote_id')) {
                $incrementId = base64_decode($quoteId);
            }else{
                $incrementId = $request->getParam('quote_identifier');
            }
            $quote = $this->quoteRepository->getByIncrementId($incrementId);
            $type = $request->getParam('type');
            
            if(
                $quote->getData('customer_lastname') != $request->getParam('customer_lastname') ||
                $quote->getData($type) != $request->getParam($type)
            ) {
                throw new InputException(__('Your quote information is incorrect.'));
            }
            
            $this->coreRegistry->register('current_quote', $quote);
            $this->coreRegistry->register('quote', $quote);
            $this->customerSession->setData(self::QUOTATION_GUEST_KEY, $quote->getId());
            return true;
        } catch(NoSuchEntityException $e){
            $this->messageManager->addError($e->getMessage());
            return $this->resultRedirectFactory->create()->setPath('quotation/guest');
        }catch (InputException $e) {
            $this->messageManager->addError($e->getMessage());
            return $this->resultRedirectFactory->create()->setPath('quotation/guest');
        }
    }
    
    /**
     * Get Breadcrumbs for current controller action
     *
     * @param \Magento\Framework\View\Result\Page $resultPage
     * @return void
     */
    public function getBreadcrumbs(\Magento\Framework\View\Result\Page $resultPage)
    {
        $breadcrumbs = $resultPage->getLayout()->getBlock('breadcrumbs');
		
		if (!$breadcrumbs) {
            return;
        }
		
        $breadcrumbs->addCrumb(
            'home',
            [
                'label' => __('Home'),
                'title' => __('Go to Home Page'),
                'link' => $this->storeManager->getStore()->getBaseUrl()
            ]
        );
        $breadcrumbs->addCrumb(
            'cms_page',
            ['label' => __('Quote Information'), 'title' => __('Quote #%1', $this->coreRegistry->registry('current_quote')->getIncrementId())]
        );
    }
}