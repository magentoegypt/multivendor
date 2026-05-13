<?php

namespace Vnecoms\VendorsCms\Controller\Vendors\App;

class Validate extends \Vnecoms\VendorsCms\Controller\Vendors\App
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_VendorsCms::app';
    /**
     * Validate action.
     */
    public function execute()
    {
        $response = new \Magento\Framework\DataObject();
        $response->setError(false);
        $app = $this->_initApp();
        $result = $app->validate();
        if ($result !== true && is_string($result)) {
            $this->messageManager->addError($result);
            $this->_view->getLayout()->initMessages();
            $response->setError(true);
            $response->setHtmlMessage($this->_view->getLayout()->getMessagesBlock()->getGroupedHtml());
        }
        $response = $response->toJson();
        $this->_translateInline->processResponseBody($response);
        $this->_response->representJson($response);
    }
}
