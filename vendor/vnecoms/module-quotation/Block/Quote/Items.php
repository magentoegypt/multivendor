<?php

namespace Vnecoms\Quotation\Block\Quote;

use Magento\Framework\Pricing\PriceCurrencyInterface;

class Items extends \Vnecoms\Quotation\Block\Quotepage
{
    protected $coreRegistry;

    protected $helper;

    /** @var PriceCurrencyInterface $priceCurrency */
    protected $priceCurrency;

    /**
     * @var \Vnecoms\Quotation\Model\QuoteFactory
     */
    protected $quoteFactory;

    /**
     * Items constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Vnecoms\Quotation\Model\Session $session
     * @param \Magento\Framework\Registry $registry
     * @param \Vnecoms\Quotation\Helper\Data $helper
     * @param PriceCurrencyInterface $priceCurrency
     * @param \Vnecoms\Quotation\Model\QuoteFactory $quote
     * @param array $data
     */
    public function __construct
    (
        \Magento\Framework\View\Element\Template\Context $context,
        \Vnecoms\Quotation\Model\Session $session,
        \Magento\Framework\Registry $registry,
        \Vnecoms\Quotation\Helper\Data $helper,
        PriceCurrencyInterface $priceCurrency,
        \Vnecoms\Quotation\Model\QuoteFactory $quote,
        array $data = []
    )
    {
        parent::__construct($context, $session, $data);
        $this->helper = $helper;
        $this->coreRegistry = $registry;
        $this->priceCurrency = $priceCurrency;
        $this->quoteFactory = $quote;
    }

    public function getQuote()
    {
        return $this->coreRegistry->registry('current_quote');
    }

    public function getJsLayoutMessage()
    {
        $data = [];
        $data['components']['quote-messages']['component'] = 'Vnecoms_Quotation/js/messages';
        $data['components']['quote-messages']['template'] = 'Vnecoms_Quotation/messages';
        $data['components']['quote-messages']['messagesJson'] = $this->getMessagesJson();
        $data['components']['quote-messages']['quote_id'] = $this->getQuote()->getId();
        $data['components']['quote-messages']['allowCustomerUpload'] = $this->helper->allowCustomerUpload();
        $data['components']['quote-messages']['uploadUrl'] = $this->getUrl('quotation/message/attach');
        $data['components']['quote-messages']['addMessageUrl'] = $this->getUrl('quotation/message/send');
        $data['components']['quote-messages']['children'][] = [
            'component' => 'Vnecoms_Quotation/js/uploader',
            'template' => 'Vnecoms_Quotation/upload/uploader',
            'displayArea' => 'uploader',
            'inputName' => 'image',
            'uploaderConfig' => [
                'url' => $this->getUrl('quotation/message/upload'),
                'acceptFileTypes' => explode(',',$this->helper->getAllowExtensions()),
                'maxFileSize' => $this->helper->getMaxSize(), //by bytes
                'maxFileNumber' => $this->helper->getMaxNumber() //default 5 files
            ]
        ];
        $data['components']['quote-messages']['children'][] = [
            'component' => 'Vnecoms_Quotation/js/history',
            'template' => 'Vnecoms_Quotation/history',
            'displayArea' => 'history',
            'messages' => $this->getMessagesJson()
        ];

        return \Laminas\Json\Json::encode($data);
    }


    /**
     * @return string
     */
    public function getMessagesJson()
    {
        $messages = $this->getQuote()->getMessages();
        if ($messages !== false and isset($messages['items']))
            return \Laminas\Json\Json::encode($messages['items']);
        return \Laminas\Json\Json::encode(array());
    }

    /**
     * Function getFormatedPrice
     *
     * @param float $price
     *
     * @return string
     */
    public function getFormatedPrice($amount)
    {
        return $this->priceCurrency->convertAndFormat($amount);
    }
}
