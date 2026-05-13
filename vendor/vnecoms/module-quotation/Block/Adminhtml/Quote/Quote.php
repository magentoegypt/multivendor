<?php
/**
 * Copyright (c) 2017 Vnecoms Co ltd. All rights reserved.
 */
namespace Vnecoms\Quotation\Block\Adminhtml\Quote;


class Quote extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * Session quote
     *
     * @var \Vnecoms\Quotation\Model\Backend\Session
     */
    protected $_session;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Vnecoms\Quotation\Model\Backend\Session $sessionQuote
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Vnecoms\Quotation\Model\Backend\Session $sessionQuote,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->_session = $sessionQuote;
        $this->registry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * @return mixed|\Vnecoms\Quotation\Model\Quote
     */
    public function getQuote()
    {
        $this->_session->getQuote();
    }

    /**
     * get quote in edit page
     * @return \Vnecoms\Quotation\Model\Quote
     */
    public function getCurrentQuote()
    {
        return $this->registry->registry('current_quote');
    }


    public function isEditPage()
    {
        return (bool) $this->getRequest()->getParam('quote_id');
    }

    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_objectId = 'quote_id';
        $this->_controller = 'quote';
        $this->_mode = 'edit';

        parent::_construct();

        if ($this->isEditPage()) $this->setId('quotation_quote_edit');
        else $this->setId('quotation_quote_create');

        $this->removeButton('reset');
        $this->removeButton('delete');
        
        $customerId = $this->_session->getCustomerId();
        $storeId = $this->_session->getStoreId();

        //edit page
        if ($this->isEditPage()) {
            $this->removeButton('save');
            $this->removeButton('cancel');
            if ($this->getCurrentQuote()->canCancel()) {
                $this->addButton(
                    'cancel_quote',
                    [
                        'label' => __('Cancel'),
                        'class' => 'cancel',
                        'onclick' => 'deleteConfirm(\'' . __(
                            'Are you sure you want to cancel this quote?'
                        ) . '\', \'' . $this->getCancelUrl() . '\')'
                    ]
                );
            }
            
            //hold
            if ($this->getCurrentQuote()->canHold()) {
                $this->buttonList->add('hold', [
                    'label' => __('Hold'),
                    'id' => 'quote-view-hold-button',
                    'class' => 'button',
                    'data_attribute' => [
                        'url' => $this->getHoldUrl()
                    ]
                ]);
            }

            //unhold
            if ($this->getCurrentQuote()->canUnhold()) {
                $this->buttonList->add('unhold', [
                    'label' => __('Unhold'),
                    'id' => 'quote-view-unhold-button',
                    'class' => 'button',
                    'data_attribute' => [
                        'url' => $this->getUnHoldUrl()
                    ]
                ]);
            }

/*             $this->buttonList->add('print', [
                'label' => __('Print'),
                'class' => 'print',
                'onclick' => 'setLocation(\'' . $this->getPrintUrl() . '\')'
            ]); */
            
            
            if ($this->getCurrentQuote()->canApprove()) {
                //add accept
                $this->buttonList->add('approve', [
                    'label' => __('Approve'),
                    'class' => 'primary button',
                    'onclick' => 'setLocation(\'' . $this->getApproveUrl() . '\')'
                ]);
            }
        }else{
            $this->removeButton('save');
            $this->buttonList->add('save_and_send', [
                'label' => __('Save and send email'),
                'class' => 'primary',
                'onclick' => 'quote.send()',
                'style' => $customerId === null || !$storeId?'display:none':''
            ]);
            $this->buttonList->add('submit_quote_top_button', [
                'label' => __('Save as draft'),
                'class' => 'primary',
                'data_attribute' => [],
                'onclick' => 'quote.submit()',
                'style' => $customerId === null || !$storeId?'display:none':''
            ]);
        }
    }

    public function getPrintUrl()
    {
        return $this->getUrl('quotation/*/print', ['quote_id' => $this->getCurrentQuote()->getId()]);
    }

    public function getApproveUrl()
    {
        return $this->getUrl('quotation/*/approve', ['quote_id' => $this->getCurrentQuote()->getId()]);

    }

    public function getRejectUrl()
    {
        return $this->getUrl('quotation/*/reject', ['quote_id' => $this->getCurrentQuote()->getId()]);

    }

    public function getHoldUrl()
    {
        return $this->getUrl('quotation/*/hold', ['quote_id' => $this->getCurrentQuote()->getId()]);

    }

    public function getUnHoldUrl()
    {
        return $this->getUrl('quotation/*/unhold', ['quote_id' => $this->getCurrentQuote()->getId()]);

    }

    public function getEmailUrl()
    {
        return $this->getUrl('quotation/*/email', ['quote_id' => $this->getCurrentQuote()->getId()]);
    }

    /**
     * {@inheritdoc}
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        $pageTitle = $this->getLayout()->createBlock('Vnecoms\Quotation\Block\Adminhtml\Quote\Edit\Header')->toHtml();
        if (is_object($this->getLayout()->getBlock('page.title'))) {
            $this->getLayout()->getBlock('page.title')->setPageTitle($pageTitle);
        }
        return parent::_prepareLayout();
    }

    /**
     * Prepare header html
     *
     * @return string
     */
    public function getHeaderHtml()
    {
        $out = '<div id="quote-header">' . $this->getLayout()->createBlock(
            'Vnecoms\Quotation\Block\Adminhtml\Quote\Create\Header'
        )->toHtml() . '</div>';
        return $out;
    }

    /**
     * Get header width
     *
     * @return string
     */
    public function getHeaderWidth()
    {
        return 'width: 70%;';
    }

    /**
     * Retrieve quote session object
     *
     * @return \Vnecoms\Quotation\Model\Backend\Session
     */
    protected function _getSession()
    {
        return $this->_session;
    }

    /**
     * Get cancel url
     *
     * @return string
     */
    public function getCancelUrl()
    {
        $url = $this->getUrl('quotation/*/cancel');

        if ($this->isEditPage()) {
            $url = $this->getUrl('quotation/*/cancel',['quote_id' => $this->getCurrentQuote()->getId()]);
        }

        return $url;
    }

    /**
     * Get URL for back (reset) button
     *
     * @return string
     */
    public function getBackUrl()
    {
        return $this->getUrl('quotation/' . $this->_controller . '/');
    }
}
