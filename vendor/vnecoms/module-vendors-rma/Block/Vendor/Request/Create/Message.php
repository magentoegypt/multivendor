<?php
/**
 * Created by PhpStorm.
 * User: nvhai
 * Date: 20/07/2016
 * Time: 15:53
 */
namespace Vnecoms\VendorsRMA\Block\Vendor\Request\Create;

class Message extends \Magento\Backend\Block\Template
{
    /**
     * Block template
     *
     * @var string
     */
    protected $_template = 'Vnecoms_RMA::request/create/message.phtml';

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Framework\Json\EncoderInterface
     */
    protected $jsonEncoder;
    /**
     * Vnecoms/RMA/Helper/Config
     *
     * @var Ticket
     */
    protected $_helper;
    /**
     * AssignProducts constructor.
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Vnecoms\RMA\Helper\Config $helper,
        array $data = []
    ) {
        $this->registry = $registry;
        $this->jsonEncoder = $jsonEncoder;
        $this->_helper = $helper;
        parent::__construct($context, $data);
    }
    /**
     * get Template
     * @return array
     */
    public function getTemplateOptions(){
        $data = array();
        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $reponse = $object_manager->get('\Vnecoms\RMA\Ui\Component\Request\Reponse');
        $data =  $reponse->getOptionArrayGrid();
        return $data;
    }

    /**
     * get Template
     * @return array
     */
    public function getTemplateJson(){
        $data = array();
        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $objectTemplate = $object_manager->get('\Vnecoms\RMA\Ui\Component\Request\Reponse');
        $templates =  $objectTemplate->getTemplates();
        return json_encode($templates->getData());
    }

    /**
     * get extension Upload Html
     */
    public function getUploaderExtensionNote() {
        return $this->_helper->allowFileExtension();
    }

}
