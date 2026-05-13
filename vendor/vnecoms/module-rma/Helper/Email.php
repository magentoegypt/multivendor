<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Email
{
    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $_transportBuilder;

    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    protected $inlineTranslation;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;


    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Vnecoms\RMA\Model\Request\UploadTransportBuilder $transportBuilder,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->_transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    /**
     * Send transaction email
     * @param string $templateIdentifier
     * @param string $area
     * @param string $fromEmailIdentifier
     * @param string|array $toEmail
     * @param string $replyTo
     * @param int $storeId
     * @param array $templateVars
     * @param string $scope
     */
    public function sendTransactionEmail(
        $templateIdentifier,
        $fromEmailIdentifier,
        $toEmail,
        $templateVars = [],
        $replyTo = '',
        $attachments = [],
        $area = \Magento\Framework\App\Area::AREA_FRONTEND,
        $storeId = \Magento\Store\Model\Store::DEFAULT_STORE_ID,
        $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
        $defaultModel = 'Magento\Email\Model\BackendTemplate'
    ) {

        $this->inlineTranslation->suspend();
        $sender = [
            'email' => $this->scopeConfig->getValue('trans_email/ident_'.$fromEmailIdentifier.'/email', $scope),
            'name' => $this->scopeConfig->getValue('trans_email/ident_'.$fromEmailIdentifier.'/name', $scope),
        ];
        $transportBuilder = $this->_transportBuilder
            ->setTemplateIdentifier($templateIdentifier)
            ->setTemplateModel($defaultModel)
            ->setTemplateOptions(
                [
                    'area' => $area,
                    'store' => $storeId,
                ]
            )
            ->setTemplateVars($templateVars)
            ->setFrom($sender)
            ->setReplyTo($replyTo ? $replyTo: $sender["email"]);
        if (is_array($toEmail)) {
            foreach ($toEmail as $em) {
                $transportBuilder->addTo($em);
            }
        } else {
            $transportBuilder->addTo($toEmail);
        }

        if (count($attachments)) {
            $transportBuilder->addAttachmentFiles($attachments);
        }

        $transport = $transportBuilder->getTransport();
        try {
            $transport->sendMessage();
            $this->inlineTranslation->resume();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
