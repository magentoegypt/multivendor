<?php
namespace Vnecoms\RMA\Ui\Component\Request;

use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\AbstractComponent;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponentInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\Registry;

class OtherReason extends \Magento\Ui\Component\Form\Field
{

    /**
     * Core registry
     *
     * @var Registry
     */
    protected $_coreRegistry = null;

    protected $_storeManager;

    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    protected $_assetRepo;


    /**
     * get helper Ticket
     *
     * @var \Vnecoms\RMA\Helper\Config
     */
    protected $_helperRequest;

    /**
     * Constructor
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UiComponentInterface[] $components
     * @param Registry $coreRegistry
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        Registry $coreRegistry,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Vnecoms\RMA\Helper\Config $helper,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->_coreRegistry = $coreRegistry;
        $this->_storeManager = $storeManager;
        $this->_assetRepo = $assetRepo;
        $this->_helperRequest = $helper;
    }

    /**
     * Prepare component configuration
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function prepare()
    {
        parent::prepare();
        $config = $this->getData('config');
        if (!$this->_helperRequest->allowOtherReasons()) {
              $config['visible'] = false;
        }
        $this->setConfig($config);
    }
}
