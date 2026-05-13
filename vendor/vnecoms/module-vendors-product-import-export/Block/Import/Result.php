<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsProductImportExport\Block\Import;

use Magento\Framework\View\Element\Template;

/**
 * Import frame result block.
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Result extends Template
{
    /**
     * JavaScript actions for response.
     *     'clear'           remove element from DOM
     *     'innerHTML'       set innerHTML property (use: elementID => new content)
     *     'value'           set value for form element (use: elementID => new value)
     *     'show'            show specified element
     *     'hide'            hide specified element
     *     'removeClassName' remove specified class name from element
     *     'addClassName'    add specified class name to element
     *
     * @var array
     */
    protected $_actions = [
        'clear' => [],
        'innerHTML' => [],
        'value' => [],
        'show' => [],
        'hide' => [],
        'removeClassName' => [],
        'addClassName' => [],
    ];

    /**
     * Validation messages.
     *
     * @var array
     */
    protected $_messages = ['error' => [], 'success' => [], 'notice' => []];

    /**
     * @var \Magento\Framework\Json\EncoderInterface
     */
    protected $_jsonEncoder;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        array $data = []
    ) {
        $this->_jsonEncoder = $jsonEncoder;
        parent::__construct($context, $data);
    }

    /**
     * Add error message.
     *
     * @param string $message Error message
     * @return $this
     */
    public function addError($message)
    {
        if (is_array($message)) {
            foreach ($message as $row) {
                $this->addError($row);
            }
        } else {
            $this->_messages['error'][] = $message;
        }
        return $this;
    }

    /**
     * Add notice message.
     *
     * @param string[]|string $message Message text
     * @param bool $appendImportButton OPTIONAL Append import button to message?
     * @return $this
     */
    public function addNotice($message, $appendImportButton = false)
    {
        if (is_array($message)) {
            foreach ($message as $row) {
                $this->addNotice($row);
            }
        } else {
            $this->_messages['notice'][] = $message . ($appendImportButton ? $this->getImportButtonHtml() : '');
        }
        return $this;
    }

    /**
     * Add success message.
     *
     * @param string[]|string $message Message text
     * @param bool $appendImportButton OPTIONAL Append import button to message?
     * @return $this
     */
    public function addSuccess($message, $appendImportButton = false)
    {
        if (is_array($message)) {
            foreach ($message as $row) {
                $this->addSuccess($row);
            }
        } else {
            $this->_messages['success'][] = $message . ($appendImportButton ? $this->getImportButtonHtml() : '');
        }
        return $this;
    }

    /**
     * Import button HTML for append to message.
     *
     * @return string
     */
    public function getImportButtonHtml()
    {
        return '&nbsp;&nbsp;<button onclick="setLocation(\'' .
            $this->getImportUrl() .
            '\');" class="btn btn-default bg-black"' .
            ' type="button"><span><i class="fa fa-angle-left"></i> ' .
            __(
                'Back To Import Queue'
            ) . '</span></button>
            &nbsp;or&nbsp;<button onclick="setLocation(\'' .
            $this->getStartImportUrl() .
            '\');" class="btn btn-primary"' .
            ' type="button"><span><i class="fa fa-rocket"></i> ' .
            __(
                'Start Queue'
            ) . '</span></button>';
    }
    
    /**
     * Get import URL
     *
     * @return string
     */
    public function getImportUrl()
    {
        return $this->getUrl('catalog/import');
    }
    
    /**
     * Get Start import URL
     *
     * @return string
     */
    public function getStartImportUrl()
    {
        return $this->getUrl('catalog/import/startQueue');
    }
    
    /**
     * Messages getter.
     *
     * @return array
     */
    public function getMessages()
    {
        return $this->_messages;
    }

    /**
     * Messages rendered HTML getter.
     *
     * @return string
     */
    public function getMessagesHtml()
    {
        /** @var $messagesBlock \Magento\Framework\View\Element\Messages */
        $messagesBlock = $this->_layout->createBlock('Magento\Framework\View\Element\Messages');

        foreach ($this->_messages as $priority => $messages) {
            $method = "add{$priority}";

            foreach ($messages as $message) {
                $messagesBlock->{$method}($message);
            }
        }
        return $messagesBlock->toHtml();
    }
}
