<?php

namespace Vnecoms\RMA\Block\Request\Email;

use Magento\Framework\Registry;

class Items extends \Magento\Framework\View\Element\Template
{

    /**
     * @var \Magento\Sales\Model\Order\Item
     */
    protected $_item;

    /**
     * @var \Vnecoms\RMA\Model\RequestFactory
     */
    protected $requestFactory;

    /**
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * Items constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Sales\Model\Order\ItemFactory $item
     * @param \Vnecoms\RMA\Model\RequestFactory $requestFactory
     * @param Registry $coreRegistry
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Sales\Model\Order\ItemFactory $item,
        \Vnecoms\RMA\Model\RequestFactory $requestFactory,
        Registry $coreRegistry,
        array $data = []
    ) {

        $this->_item = $item;
        $this->_coreRegistry = $coreRegistry;
        $this->requestFactory = $requestFactory;
        parent::__construct($context, $data);
    }



    public function getItems()
    {
        $items = $this->getRma()->getAllItemFromRequest();
        return $items;
    }


    public function getItemName($itemId)
    {
        $item = $this->_item->create()->load($itemId);
        return $item->getName();
    }

    /**
     * @return mixed
     */
    public function getRma()
    {
        $rma = $this->getData('rma');
        if ($rma !== null) {
            return $rma;
        }

        $rmaId = (int)$this->getData('rma_id');
        if ($rmaId) {
            $rma = $this->requestFactory->create()->load($rmaId);
            $this->setData('rma', $rma);
        }

        return $this->getData('rma');
    }
}
