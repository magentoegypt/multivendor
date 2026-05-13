<?php
/**
 *
 * Copyright © Vnecoms. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsRMA\Controller\Adminhtml\Email;

use Magento\Framework\App\TemplateTypesInterface;

class Save extends Template
{
    /**
     * Save transactional email action
     *
     * @return void
     */
    public function execute()
    {
        $request = $this->getRequest();
        $id = $this->getRequest()->getParam('id');

        $template = $this->_initTemplate('id');
        if (!$template->getId() && $id) {
            $this->messageManager->addError(__('This email template no longer exists.'));
            $this->_redirect('vrma/*/');
            return;
        }

        try {
            $template->setTemplateSubject(
                $request->getParam('template_subject')
            )->setTypeSendMail(
                $request->getParam('type_send_mail')
            )->setTemplateCode(
                $request->getParam('template_code')
            )->setTemplateText(
                $request->getParam('template_text')
            )->setTemplateStyles(
                $request->getParam('template_styles')
            )->setModifiedAt(
                $this->_objectManager->get('Magento\Framework\Stdlib\DateTime\DateTime')->gmtDate()
            )->setOrigTemplateCode(
                $request->getParam('orig_template_code')
            )->setOrigTemplateVariables(
                $request->getParam('orig_template_variables')
            )->setUseCustomMessage(
                $request->getParam('use_custom_message')
            );

            if (!$template->getId()) {
                $template->setTemplateType(TemplateTypesInterface::TYPE_HTML);
            }

            if ($request->getParam('_change_type_flag')) {
                $template->setTemplateType(TemplateTypesInterface::TYPE_TEXT);
                $template->setTemplateStyles('');
            }

            $template->save();
            $this->_objectManager->get('Magento\Backend\Model\Session')->setFormData(false);
            $this->messageManager->addSuccess(__('You saved the email template.'));
            $this->_redirect('vrma/*');
        } catch (\Exception $e) {
            $this->_objectManager->get(
                'Magento\Backend\Model\Session'
            )->setData(
                'email_template_form_data',
                $request->getParams()
            );
            $this->messageManager->addError($e->getMessage());
            $this->_forward('new');
        }
    }
}
