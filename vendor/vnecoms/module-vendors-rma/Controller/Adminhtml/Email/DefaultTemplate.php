<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsRMA\Controller\Adminhtml\Email;

class DefaultTemplate extends Template
{
    /**
     * @var \Magento\Email\Model\Template\Config
     */
    private $emailConfig;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Email\Model\Template\Config $emailConfig
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Email\Model\Template\Config $emailConfig
    ) {
        $this->emailConfig = $emailConfig;
        parent::__construct($context, $coreRegistry);
    }

    /**
     * Set template data to retrieve it in template info form
     *
     * @return void
     */
    public function execute()
    {
        $this->_view->loadLayout();
        $template = $this->_initTemplate('id');
        $templateId = $this->getRequest()->getParam('code');
        try {
            $parts = $this->emailConfig->parseTemplateIdParts($templateId);
            $templateId = $parts['templateId'];
            $theme = $parts['theme'];

            if ($theme) {
                $template->setForcedTheme($templateId, $theme);
            }
            $template->setForcedArea($templateId);

            $template->loadDefault($templateId);
            $template->setData('orig_template_code', $templateId);
            $template->setData('template_variables', \Laminas\Json\Json::encode($template->getVariablesOptionArray(true)));

            $templateBlock = $this->_view->getLayout()->createBlock('Vnecoms\VendorsRMA\Block\Adminhtml\Template\Edit');
            $template->setData('orig_template_currently_used_for', $templateBlock->getCurrentlyUsedForPaths(false));

            $this->getResponse()->representJson(
                $this->_objectManager->get('Magento\Framework\Json\Helper\Data')->jsonEncode($template->getData())
            );
        } catch (\Exception $e) {
            $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
        }
    }
}
