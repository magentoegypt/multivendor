<?php

namespace Vnecoms\Quotation\Controller\Checkout;

use Magento\Framework\App\Action\Context;

class SendQuote extends \Magento\Framework\App\Action\Action {

    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    protected $_formKeyValidator;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    public function __construct
    (
        Context $context,
        \Magento\Framework\Data\Form\FormKey\Validator $validator,
        \Magento\Checkout\Model\Session $session
    )
    {
        parent::__construct($context);
        $this->_formKeyValidator = $validator;
        $this->_checkoutSession = $session;
    }

    public function execute()
    {
        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }

        try {
            $data = $this->getRequest()->getPost();


        } catch (\Exception $exception) {

        }
    }
}