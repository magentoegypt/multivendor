<?php
/**
 * Copyright (c) 2017 Vnecoms Co ltd. All rights reserved.
 */

namespace Vnecoms\Quotation\Model;

use Vnecoms\Quotation\Api\Data\ProposalInterface;

/**
 * Class Proposal
 * @package Vnecoms\Quotation\Model
 * @method string getItemId()
 * @method Proposal setItemId($id)
 * @method string getPrice()
 * @method string getBasePrice()
 * @method Proposal setBasePrice($price)
 * @method string getComment()
 * @method Proposal setComment($comment)
 * @method string getMarginGp()
 * @method Proposal setMarginGp($gp)
 */

class Proposal extends \Magento\Framework\Model\AbstractModel implements ProposalInterface
{
    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'ves_quotation_item_proposal';

    /**
     * Parameter name in event
     *
     * In observe method you can use $observer->getEvent()->getObject() in this case
     *
     * @var string
     */
    protected $_eventObject = 'item_proposal';

    /**
     * @var Item
     */
    protected $_item;

    /**
     * @var \Magento\Framework\Locale\FormatInterface
     */
    protected $_localeFormat;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * Proposal constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Locale\FormatInterface $format
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct
    (
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Locale\FormatInterface $format,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        $this->_localeFormat = $format;
        $this->priceCurrency = $priceCurrency;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Get proposal_id
     * @return string
     */
    public function getProposalId()
    {
        return $this->getData(self::PROPOSAL_ID);
    }

    /**
     * Set proposal_id
     * @param string $proposalId
     * @return \Vnecoms\Quotation\Api\Data\ProposalInterface
     */
    public function setProposalId($proposalId)
    {
        return $this->setData(self::PROPOSAL_ID, $proposalId);
    }

    /**
     * Get qty
     * @return float
     */
    public function getQty()
    {
        return $this->getData(self::QTY);
    }

    /**
     * Prepare quantity
     *
     * @param float|int $qty
     * @return int|float
     */
    protected function _prepareQty($qty)
    {
        $qty = $this->_localeFormat->getNumber($qty);
        $qty = $qty > 0 ? $qty : 1;
        return $qty;
    }

    /**
     * Declare proposal quantity
     *
     * @param float $qty
     * @return $this
     */
    public function setQty($qty)
    {
        $qty = $this->_prepareQty($qty);
        $this->setData('qty', $qty);

        return $this;
    }

    /**
     * @param $qty
     * @return $this
     */
    public function addQty($qty)
    {
        $qty = $this->_prepareQty($qty);
        $this->setQty($this->getQty() + $qty);

        return $this;
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Vnecoms\Quotation\Model\ResourceModel\Proposal');
    }

    /**
     * Init Proposal
     *
     * @param Item $item
     * @param $params
     * @return $this
     */
    public function init(Item $item, $params)
    {
        if ($this->getId() && $item->getParentItem()) {
            return $this;
        }

        $this->setPrice((float) $params->getPrice())
            ->setBasePrice((float) $params->getBasePrice())
            ->setQty($params->getData('qty'))
            ;

        return $this;
    }

    /**
     * @return Item
     */
    public function getItem()
    {
        if($this->_item === null){
            $om = \Magento\Framework\App\ObjectManager::getInstance();
            $this->_item = $om->create('Vnecoms\Quotation\Model\Item')->load($this->getItemId());
        }
        return $this->_item;
    }

    /**
     * Get Quote
     * 
     * @return \Vnecoms\Quotation\Model\Quote
     */
    public function getQuote(){
        return $this->getItem()->getQuote();
    }
    
    /**
     * @param Item $item
     */
    public function setItem(\Vnecoms\Quotation\Model\Item $item)
    {
        $this->_item = $item;
        if ($item->getId()) $this->setItemId($item->getId());

        return $this;
    }


    /**
     *
     */
    public function beforeSave()
    {
        return parent::beforeSave();
    }

    /**
     * Specify item price (base calculation price and converted price will be refreshed too)
     *
     * @param   float $value
     * @return  $this
     */
    public function setPrice($value)
    {
        $this->setConvertedPrice(null);
        return $this->setData('price',$value);
    }

    /**
     * @return \Magento\Store\Model\Store
     */
    public function getStore()
    {
        return $this->getItem()->getStore();
    }

    /**
     * Get item price converted to quote currency
     * @return float
     */
    public function getConvertedPrice()
    {
        $price = $this->_getData('converted_price');
        if ($price === null) {
            $price = $this->priceCurrency->convert($this->getPrice(), $this->getStore());
            $this->setData('converted_price', $price);
        }
        return $price;
    }

    public function getCalculationPrice()
    {
        return $this->getConvertedPrice();
    }

    /**
     * Set new value for converted price
     * @param float $value
     * @return $this
     */
    public function setConvertedPrice($value)
    {
        $this->setData('converted_price', $value);
        return $this;
    }
    
    /**
     * @return boolean
     */
    public function isDefault(){
        return (bool) $this->getIsDefault();
    }
}
