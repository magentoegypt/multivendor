<?php

namespace Vnecoms\RMA\Block\Frontend\View;

class Message extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;
    /**
     * @var \Vnecoms\RMA\Helper\Config
     */
    protected $_config;

    /**
     * @var \Vnecoms\RMA\Model\ResourceModel\Request\Collection
     */
    protected $_requestCollection;

    /**
     * NewRequest constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Vnecoms\RMA\Helper\Config $config
     * @param \Vnecoms\RMA\Model\ResourceModel\Request\CollectionFactory $requestCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Vnecoms\RMA\Helper\Config $config,
        \Magento\Framework\Registry $registry,
        \Vnecoms\RMA\Model\Request $requestFactory,
        \Vnecoms\RMA\Helper\Config $helper,
        array $data = []
    ) {
        $this->_customerSession     = $customerSession;
        $this->_config              = $config;
        $this->_coreRegistry        = $registry;
        $this->_requestCollection   = $requestFactory;
        $this->_helper = $helper;
        parent::__construct($context, $data);
    }
    /**
     * check is show button reply
     * @return bool
     */
    public function isShowButtonReply()
    {
        if ($this->getRequestRma()->getState() == \Vnecoms\RMA\Model\Request::STATE_OPEN) {
            return true;
        }
        return false;
    }

    /**
     * get class header link
     * @return null
     */
    public function getClassHeaderLink()
    {
        return null;
    }

    /**
     * allow closed request
     * @return boolc
     */
    public function isEnableCloseRequest()
    {
        return true;
    }

    /**
     * allow edit message
     * @return bool
     */
    public function isShowButtonEdit()
    {
        return true;
    }

    public function _prepareLayout()
    {
        $button = $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button')
            ->setData([
                "type"=>"submit",
                'label' => __('Submit'),
                //  'onclick'   => 'vesticket.submit();',
                'class' => 'primary ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only'
            ]);
        $button->setName('send_message');
        $this->setChild('button_submit', $button);
        return parent::_prepareLayout();
    }

    /**
     * get Child Button Supmit Html
     * @return mixed
     */
    public function getChildButtonHtml()
    {
        return $this->getChildHtml('button_submit');
    }

    /**
     * get Curent Request Data
     * @return mixed
     */
    public function getRequestRma()
    {
        if ($this->hasData("rma")) {
            return $this->getData("rma");
        }
        return $this->_coreRegistry->registry("current_request");
    }

    /**
     * get Send Name Message
     * @param $type
     * @return mixed
     */
    public function getDisplayName($type)
    {
        $request = $this->getRequestRma();
        switch ($type) {
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
    public function getFormatDateHtml($date)
    {
        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $dateObj = $object_manager->get('\Magento\Framework\Stdlib\DateTime\DateTime');
        return $dateObj->date('F j, Y, g:i a', $date);
    }

    /**
     * get Template
     * @return array
     */
    public function getTemplateOptions()
    {
        $data = [];
        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $status = $object_manager->get('\Vnecoms\RMA\Ui\Component\Request\Reponse');
        $data =  $status->getOptionArrayGrid();
        return $data;
    }


    /**
     * get Template
     * @return array
     */
    public function getTemplateJson()
    {
        $data = [];
        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $objectTemplate = $object_manager->get('\Vnecoms\RMA\Ui\Component\Request\Reponse');
        $templates =  $objectTemplate->getTemplates();
        return json_encode($templates->getData());
    }


    /**
     * @param $message
     * @return mixed
     */
    public function getTextHeaderMessage($message)
    {
        return $this->_helper->wordLimiter($message, 140);
    }
    /**
     * check message is html
     * @param $message
     * @return bool
     */
    public function isHtmlMessage($message)
    {
        return ($message != strip_tags($message));
    }

    /**
     * get class for file attachment message
     * @param $file
     * @return mixed
     */
    public function getClassIcon($file)
    {
        return  $this->_helper->getClassIcon($file);
    }

    /**
     * check Image extenstion
     */
    public function isImage($file)
    {
        if (in_array($this->getClassIcon($file), ["icon-jpg","icon-jpeg","icon-jpeg","icon-png","icon-gif"])) {
            return true;
        }
        return false;
    }

    public function getHtmlEditor()
    {
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
        $config = [
            'label'     => __('Message'),
            'name'      => 'request[message]',
            'config' => $configwysiwyg,
            'wysiwyg' => true,
            'style' => 'width:100%; height:250px;',
            'required'=> true,
            'class' => " required-entry",
            "validation" => [
                "required-entry" => true
            ]
        ];
        $form = $object_manager->get('\Magento\Framework\Data\Form');
        $editor = $object_manager->get('\Magento\Framework\Data\Form\Element\Editor')->setData($config);
        $editor->setForm($form);
        $editor->setId($elementId);
        return $editor->getElementHtml();
    }

    /**
     * get extension Upload Html
     */
    public function getUploaderExtensionNote()
    {
        return $this->_helper->allowFileExtension();
    }
    /**
     * get custom css for wysiwyg tiny mce
     */
    public function getContentCss()
    {
        $css =  $this->_assetRepo->getUrl(
            'mage/adminhtml/wysiwyg/tiny_mce/themes/advanced/skins/default/content.css'
        );
        $css .= ",".$this->_assetRepo->getUrl(
            'Vnecoms_RMA::wysiwyg/tiny_mce/blockquote.css'
        );
        return $css;
    }

    /**
     * @param object $order
     * @return string
     */
    public function getSaveUrl()
    {
        return $this->getUrl('vrma/customer/reply');
    }
}
