<?php
/**
 * Copyright © 2017 Vnecoms. All rights reserved.
 */
namespace Vnecoms\Quotation\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Email
{
    /**
     * @var \Vnecoms\Quotation\Model\MailTransportBuilder
     */
    protected $transportBuilder;
    
    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    protected $inlineTranslation;
    
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;
    
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;
    
    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Vnecoms\Quotation\Model\MailTransportBuilder $transportBuilder
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Vnecoms\Quotation\Model\MailTransportBuilder $transportBuilder,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->_logger = $logger;
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }
    
    /**
     * @param string $templateIdentifier
     * @param string $area
     * @param string $fromEmailIdentifier
     * @param string|array $toEmail
     * @param array $templateVars
     * @param array $attachments
     * @param string $replyTo
     * @param int $storeId
     * @param string $scope
     */
    public function sendTransactionEmail(
        $templateIdentifier,
        $area,
        $fromEmailIdentifier,
        $toEmail,
        $templateVars = [],
        $attachments = [],
        $replyTo = '',
        $storeId = \Magento\Store\Model\Store::DEFAULT_STORE_ID,
        $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT
    ) {
        $templateId = $this->scopeConfig->getValue($templateIdentifier, $scope, $storeId);
        if(!$templateId) return;
        
        $this->inlineTranslation->suspend();
        $transportBuilder = $this->transportBuilder
            ->setTemplateIdentifier($templateId)
            ->setTemplateOptions(
                [
                    'area' => $area,
                    'store' => $this->storeManager->getStore()->getId(),
                ]
            )
            ->setTemplateVars($templateVars)
            ->setFromByScope($this->scopeConfig->getValue($fromEmailIdentifier, $scope, $storeId), $storeId);
        $canSendEmail = false;
        
        if (is_array($toEmail)) {
            foreach ($toEmail as $em) {
                if(!$em) continue;
                $canSendEmail = true;
                $transportBuilder->addTo($em);
            }
        } else {
            $canSendEmail = true;
            $transportBuilder->addTo($toEmail);
        }


        if(!$canSendEmail) return;
        
        if(sizeof($attachments)){
            foreach($attachments as $attachment){
                $transportBuilder->addAttachment($attachment);
            }
        }
        
        $transport = $transportBuilder->getTransport();
        try {
            $transport->sendMessage();
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        
        $this->inlineTranslation->resume();
    }
}
