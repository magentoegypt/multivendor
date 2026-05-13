<?php
/**
 * Copyright (c) 2017 Vnecoms Co ltd. All rights reserved.
 */

namespace Vnecoms\Quotation\Block\Adminhtml\Quote;


class View extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * Block group
     *
     * @var string
     */
    protected $_blockGroup = 'Vnecoms_Quotation';

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * Constructor
     *
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _construct()
    {
        $this->_objectId = 'quote_id';
        $this->_controller = 'adminhtml_quote';
        $this->_mode = 'edit';

        parent::_construct();

        $this->buttonList->remove('delete');
        $this->buttonList->remove('reset');
        $this->buttonList->remove('save');
        $this->setId('quotation_quote_view');
        $quote = $this->getQuote();

        if (!$quote) {
            return;
        }

        $this->buttonList->add(
            'quote_cancel',
            [
                'label' => __('Cancel'),
                'class' => 'cancel',
                'id' => 'quote-view-cancel-button',
                'data_attribute' => [
                    'url' => $this->getCancelUrl()
                ]
            ]
        );

        $message = __('Are you sure you want to send an quote email to customer?');
        $this->addButton(
            'send_notification',
            [
                'label' => __('Send Email'),
                'class' => 'send-email',
                'onclick' => "confirmSetLocation('{$message}', '{$this->getEmailUrl()}')"
            ]
        );

        $this->buttonList->add(
            'order_hold',
            [
                'label' => __('Hold'),
                'class' => __('hold'),
                'id' => 'order-view-hold-button',
                'data_attribute' => [
                    'url' => $this->getHoldUrl()
                ]
            ]
        );

        $this->buttonList->add(
            'order_unhold',
            [
                'label' => __('Unhold'),
                'class' => __('unhold'),
                'id' => 'order-view-unhold-button',
                'data_attribute' => [
                    'url' => $this->getUnHoldUrl()
                ]
            ]
        );
    }

    /**
     * Retrieve model object
     *
     * @return \Vnecoms\Quotation\Model\Quote
     */
    public function getQuote()
    {
        return $this->_coreRegistry->registry('current_quote');
    }

    /**
     * Retrieve Identifier
     *
     * @return int
     */
    public function getQuoteId()
    {
        return $this->getQuote() ? $this->getQuote()->getId() : null;
    }

    /**
     * Get header text
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        $quoteId = $this->getQuote()->getIncrementId();

        return __(
            'Quote # %1 | %2',
            $quoteId,
            $this->formatDate(
                $this->_localeDate->date(new \DateTime($this->getQuote()->getCreatedAt())),
                \IntlDateFormatter::MEDIUM,
                true
            )
        );
    }

    /**
     * URL getter
     *
     * @param string $params
     * @param array $params2
     * @return string
     */
    public function getUrl($params = '', $params2 = [])
    {
        $params2['quote_id'] = $this->getQuoteId();
        return parent::getUrl($params, $params2);
    }

    /**
     * Email URL getter
     *
     * @return string
     */
    public function getEmailUrl()
    {
        return $this->getUrl('quotation/*/email');
    }

    /**
     * Cancel URL getter
     *
     * @return string
     */
    public function getCancelUrl()
    {
        return $this->getUrl('quotation/*/cancel');
    }

    /**
     * Hold URL getter
     *
     * @return string
     */
    public function getHoldUrl()
    {
        return $this->getUrl('quotation/*/hold');
    }

    /**
     * Unhold URL getter
     *
     * @return string
     */
    public function getUnholdUrl()
    {
        return $this->getUrl('quotation/*/unhold');
    }

    /**
     * Check permission for passed action
     *
     * @param string $resourceId
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }

    /**
     * Return back url for view grid
     *
     * @return string
     */
    public function getBackUrl()
    {
        return $this->getUrl('quotation/*/');
    }
}
