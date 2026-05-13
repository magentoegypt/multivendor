<?php

namespace Vnecoms\Quotation\Block\Adminhtml\Quote\Edit\Items;

use Magento\Framework\Pricing\PriceCurrencyInterface;

/**
 * Class Proposal
 * @package Vnecoms\Quotation\Block\Adminhtml\Quote\Edit\Items
 * @method Item getItem()
 * @method Item setItem(Item $item)
 */
class Proposal extends \Vnecoms\Quotation\Block\Adminhtml\Quote\Edit\AbstractQuote
{
    /**
     * @var \Vnecoms\Quotation\Model\Item
     */
    protected $item;

    /**
     * @var array
     */
    protected $proposals;

    /**
     * @var array
     */
    protected $jsLayout;

    /**
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Vnecoms\Quotation\Model\Backend\Session $sessionQuote
     * @param \Vnecoms\Quotation\Model\Quote $quoteCreate
     * @param PriceCurrencyInterface $priceCurrency
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Vnecoms\Quotation\Model\Backend\Session $sessionQuote,
        \Vnecoms\Quotation\Model\Quote $quoteCreate,
        PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        parent::__construct($context, $sessionQuote, $quoteCreate, $priceCurrency, $registry, $data);
        $this->jsLayout = isset($data['jsLayout']) && is_array($data['jsLayout']) ? $data['jsLayout'] : [];
    }

    /**
     * (non-PHPdoc)
     * @see \Magento\Framework\View\Element\AbstractBlock::getJsLayout()
     */
    public function getJsLayout()
    {
        $component = $this->getUiElementId();;
        $this->jsLayout['components'][$component]['itemId'] = $this->getItem()->getId();
        $this->jsLayout['components'][$component]['isEditable'] = $this->isEdiable();
        $this->jsLayout['components'][$component]['itemData'] = $this->getItem()->getData();
        $this->jsLayout['components'][$component]['proposals'] = $this->getItem()->getProposalsCollection()->setOrder('proposal_id','ASC')->getData();
        $this->jsLayout['components'][$component]['component'] = 'Vnecoms_Quotation/js/proposal';
        $this->jsLayout['components'][$component]['saveProposalUrl'] = $this->getUrl('quotation/proposal/save',['_secure' => true]);
        $this->jsLayout['components'][$component]['saveDefaultProposalUrl'] = $this->getUrl('quotation/proposal/updateDefault',['_secure' => true]);
        $this->jsLayout['components'][$component]['removeProposalUrl'] = $this->getUrl('quotation/proposal/remove',['_secure' => true]);

        return \Laminas\Json\Json::encode($this->jsLayout);
    }

    /**
     * Get UI element Id
     *
     * @return string
     */
    public function getUiElementId(){
        return 'proposal'.$this->getItem()->getId();
    }
}
