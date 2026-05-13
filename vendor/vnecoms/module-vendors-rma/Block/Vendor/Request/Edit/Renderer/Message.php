<?php
namespace Vnecoms\VendorsRMA\Block\Vendor\Request\Edit\Renderer;
use Magento\Backend\Block\Widget\Form\Renderer\Fieldset\Element;
use Magento\Framework\Registry;

class Message extends Element
{
    /**
     * Core registry
     *
     * @var Registry
     */
    protected $_coreRegistry = null;

    /**
     * Vnecoms/RMA/Model/Request
     *
     * @var Ticket
     */
    protected $_rma = null;
    /**
     * Vnecoms/RMA/Helper/Config
     *
     * @var Ticket
     */
    protected $_helper;


    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Vnecoms\RMA\Helper\Config $helper,
        Registry $coreRegistry,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->_coreRegistry = $coreRegistry;
        $this->_helper = $helper;
    }

    /**
     * check is show button reply
     * @return bool
     */
    public function isShowButtonReply(){
        if(
            $this->getRequestRma()->getState() == \Vnecoms\RMA\Model\Request::STATE_OPEN ||
            $this->getRequestRma()->getState() == \Vnecoms\VendorsRMA\Model\Request::STATE_AWAITING ||
            $this->getRequestRma()->getState() == \Vnecoms\VendorsRMA\Model\Request::STATE_BEING
        ) return true;
        return false;
    }

    /**
     * get class header link
     * @return null
     */
    public function getClassHeaderLink(){
        return null;
    }

    /**
     * allow closed request
     * @return boolc
     */
    public function isEnableCloseRequest(){
        return true;
    }

    /**
     * allow edit message
     * @return bool
     */
    public function isShowButtonEdit(){
        return true;
    }

    public function _prepareLayout()
    {
        $button = $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button')
            ->setData(array(
                "type"=>"submit",
                'label' => __('Submit'),
                //  'onclick'   => 'vesticket.submit();',
                'class' => 'primary ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only'
            ));
        $button->setName('send_message');
        $this->setChild('button_submit', $button);
        return parent::_prepareLayout();;
    }

    /**
     * get Child Button Supmit Html
     * @return mixed
     */
    public function getChildButtonHtml(){
        return $this->getChildHtml('button_submit');
    }

    /**
     * get Curent Request Data
     * @return mixed
     */
    public function getRequestRma(){
        return $this->_coreRegistry->registry("current_request");
    }

    /**
     * set Curent RMA Data
     * @return mixed
     */
    public function setRequestRma($rma){
        $this->_rma = $rma;
    }

    /**
     * get Send Name Message
     * @param $type
     * @return mixed
     */
    public function getDisplayName($type){
        $request = $this->getRequestRma();
        switch ($type){
            case \Vnecoms\RMA\Model\Source\Message\Type::TYPE_REPLY_CUSTOMER:
                $name= $request->getCustomerName();
                break;
            case \Vnecoms\RMA\Model\Source\Message\Type::TYPE_REPLY_DEPARMENT:
                $name= __("Me");
                break;
        }
        return $name;
    }

    /**
     * format date html
     * @param $date
     * @return mixed
     */
    public function getFormatDateHtml($date) {
        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $dateObj = $object_manager->get('\Magento\Framework\Stdlib\DateTime\DateTime');
        return $dateObj->date('F j, Y, g:i a',$date);
    }

    /**
     * get Template
     * @return array
     */
    public function getTemplateOptions(){
        $data = array();
        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $status = $object_manager->get('\Vnecoms\VendorsRMA\Ui\Component\Request\Reponse');
        $data =  $status->getOptionArrayGrid();
        return $data;
    }


    /**
     * get Template
     * @return array
     */
    public function getTemplateJson(){
        $data = array();
        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $objectTemplate = $object_manager->get('\Vnecoms\VendorsRMA\Ui\Component\Request\Reponse');
        $templates =  $objectTemplate->getTemplates();
        return json_encode($templates->getData());
    }


    /**
     * @param $message
     * @return mixed
     */
    public function getTextHeaderMessage($message) {
        return $this->_helper->wordLimiter($message,140);
    }
    /**
     * check message is html
     * @param $message
     * @return bool
     */
    public function isHtmlMessage($message){
        return ($message != strip_tags($message));
    }

    /**
     * get class for file attachment message
     * @param $file
     * @return mixed
     */
    public function getClassIcon($file){
        return  $this->_helper->getClassIcon($file);
    }

    /**
     * check Image extenstion
     */
    public function isImage($file){
        if(in_array($this->getClassIcon($file),array("icon-jpg","icon-jpeg","icon-jpeg","icon-png","icon-gif"))) return true;
        return false;
    }

    public function getHtmlEditor(){
        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $wysiwygConfig = $object_manager->get('\Magento\Cms\Model\Wysiwyg\Config');
        $configwysiwyg =  $wysiwygConfig->getConfig();
        $configwysiwygData = $configwysiwyg->getData();
        $configwysiwygData["settings"]["theme_advanced_buttons1"] = "bold,italic,|,justifyleft,justifycenter,justifyright,|,fontselect,fontsizeselect,|,forecolor,backcolor,|,link,unlink,image,|,bullist,numlist,|,code";
        $configwysiwygData["settings"]["theme_advanced_buttons2"] = false;
        $configwysiwygData["settings"]["theme_advanced_buttons3"] = false;
        $configwysiwygData["settings"]["theme_advanced_buttons4"] = false;
        $configwysiwygData["settings"]["theme_advanced_statusbar_location"] = false;
        $configwysiwygData["content_css"] = $this->getContentCss();
        $configwysiwygData["height"] = "250px";
        $configwysiwygData["add_variables"] = false;
        $configwysiwygData["plugins"] = false;
        $configwysiwygData["add_widgets"] = false;
        $configwysiwygData["add_images"] = false;
        $configwysiwygData["files_browser_window_url"] =false;
        $configwysiwygData["toggle_button"] = false;
        $configwysiwyg->setData($configwysiwygData);

        $elementId = "content_message_reply";
        $config = array(
            'label'     => __('Message'),
            'name'      => 'request[message]',
            'config' => $configwysiwyg,
            'wysiwyg' => true,
            'style' => 'width:100%; height:250px;',
            'required'=> true,
            'class' => " required-entry",
            "validation" => array(
                "required-entry" => true
            )
        );
        $form = $object_manager->get('\Magento\Framework\Data\Form');
        $editor = $object_manager->get('\Magento\Framework\Data\Form\Element\Editor')->setData($config);
        $editor->setForm($form);
        $editor->setId($elementId);
        return $editor->getElementHtml();
    }

    /**
     * get extension Upload Html
     */
    public function getUploaderExtensionNote() {
        return $this->_helper->allowFileExtension();
    }
    /**
     * get custom css for wysiwyg tiny mce
     */
    public function getContentCss()
    {
        $css =  $this->_assetRepo->getUrl(
            'mage/adminhtml/wysiwyg/tiny_mce/themes/advanced/skins/default/content.css');
        $css .= ",".$this->_assetRepo->getUrl(
                'Vnecoms_VendorsRMA::wysiwyg/tiny_mce/blockquote.css');
        return $css;
    }

}