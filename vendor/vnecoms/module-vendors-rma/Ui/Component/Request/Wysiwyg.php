<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsRMA\Ui\Component\Request;

use Magento\Framework\Data\Form\Element\Editor;
use Magento\Framework\Data\Form;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\DataObject;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Wysiwyg\ConfigInterface;
use Magento\Ui\Component\Form\Element\AbstractElement;

/**
 * Class Input
 */
class Wysiwyg extends AbstractElement
{
    const NAME = 'wysiwyg';

    /**
     * @var Form
     */
    protected $form;

    /**
     * @var Editor
     */
    protected $editor;

    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    protected $_assetRepo;

    /**
     * @param ContextInterface $context
     * @param FormFactory $formFactory
     * @param ConfigInterface $wysiwygConfig
     * @param array $components
     * @param array $data
     * @param array $config
     */
    public function __construct(
        ContextInterface $context,
        FormFactory $formFactory,
        ConfigInterface $wysiwygConfig,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        array $components = [],
        array $data = [],
        array $config = []
    ) {
        $this->_assetRepo = $assetRepo;
        $wysiwygConfigData = isset($config['wysiwygConfigData']) ? $config['wysiwygConfigData'] : [];
        $wysiwygConfigData["content_css"] = $this->_getContentCss();
        $this->form = $formFactory->create();
        $this->editor = $this->form->addField(
            $context->getNamespace() . '_' . $data['name'],
            'Magento\Framework\Data\Form\Element\Editor',
            [
                'force_load' => true,
                'rows' => 20,
                'name' => $data['name'],
                'config' => $wysiwygConfig->getConfig($wysiwygConfigData),
                'wysiwyg' => isset($config['wysiwyg']) ? $config['wysiwyg'] : null,
            ]
        );
        $data['config']['content'] = $this->editor->getElementHtml();

        parent::__construct($context, $components, $data);
    }


    /**
     * get custom css for wysiwyg tiny mce
     */
    protected function _getContentCss()
    {
        $css =  $this->_assetRepo->getUrl(
            'mage/adminhtml/wysiwyg/tiny_mce/themes/advanced/skins/default/content.css');
        $css .= ",".$this->_assetRepo->getUrl(
                'Vnecoms_VendorsRMA::wysiwyg/tiny_mce/blockquote.css');
        return $css;
    }


    /**
     * Get component name
     *
     * @return string
     */
    public function getComponentName()
    {
        return static::NAME;
    }
}
