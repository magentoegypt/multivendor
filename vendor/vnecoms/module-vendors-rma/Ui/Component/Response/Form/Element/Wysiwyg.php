<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsRMA\Ui\Component\Response\Form\Element;

use Magento\Backend\Block\Widget\Button;
use Magento\Backend\Helper\Data as DataHelper;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Wysiwyg\ConfigInterface;
use Magento\Framework\View\LayoutInterface;

/**
 * Catalog Wysiwyg.
 */
class Wysiwyg extends \Magento\Ui\Component\Form\Element\Wysiwyg
{
    /**
     * @var DataHelper
     */
    protected $backendHelper;

    /**
     * @var LayoutInterface
     */
    protected $layout;

    /**
     * @param ContextInterface $context
     * @param FormFactory      $formFactory
     * @param ConfigInterface  $wysiwygConfig
     * @param LayoutInterface  $layout
     * @param DataHelper       $backendHelper
     * @param array            $components
     * @param array            $data
     * @param array            $config
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        ContextInterface $context,
        FormFactory $formFactory,
        ConfigInterface $wysiwygConfig,
        LayoutInterface $layout,
        DataHelper $backendHelper,
        array $components = [],
        array $data = [],
        array $config = []
    ) {
        $this->layout = $layout;
        $this->backendHelper = $backendHelper;

        $config['wysiwyg'] = true;
        parent::__construct($context, $formFactory, $wysiwygConfig, $components, $data, $config);
        $this->setData($this->prepareData($this->getData()));
    }


}
