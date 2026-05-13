<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Model\Template;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\State;
use Magento\Framework\Css\PreProcessor\Adapter\CssInliner;
use Magento\Framework\Escaper;
use Magento\Framework\Filesystem;
use Magento\Framework\Filter\VariableResolverInterface;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Framework\View\LayoutFactory;
use Magento\Framework\View\LayoutInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Variable\Model\Source\Variables;
use Magento\Variable\Model\VariableFactory;
use Psr\Log\LoggerInterface;

/**
 * Cms Template Abbstract Filter.
 */
abstract class AbstractFilter extends \Magento\Email\Model\Template\Filter
{
    /**
     * @var \Magento\Framework\Filter\Template\Tokenizer\Parameter
     */
    protected $tokenizeParams;

    /** @var \Magento\Framework\Registry  */
    protected $_coreRegistry;

    /**
     * AbstractFilter constructor.
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Filter\Template\Tokenizer\Parameter $tokenizeParams
     * @param StringUtils $string
     * @param LoggerInterface $logger
     * @param Escaper $escaper
     * @param Repository $assetRepo
     * @param ScopeConfigInterface $scopeConfig
     * @param VariableFactory $coreVariableFactory
     * @param StoreManagerInterface $storeManager
     * @param LayoutInterface $layout
     * @param LayoutFactory $layoutFactory
     * @param State $appState
     * @param UrlInterface $urlModel
     * @param Variables $configVariables
     * @param VariableResolverInterface $variableResolver
     * @param \Magento\Email\Model\Template\Css\Processor $cssProcessor
     * @param Filesystem $pubDirectory
     * @param CssInliner $cssInliner
     * @param array $variables
     * @param array $directiveProcessors
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Filter\Template\Tokenizer\Parameter $tokenizeParams,
        StringUtils $string,
        LoggerInterface $logger,
        Escaper $escaper,
        Repository $assetRepo,
        ScopeConfigInterface $scopeConfig,
        VariableFactory $coreVariableFactory,
        StoreManagerInterface $storeManager,
        LayoutInterface $layout,
        LayoutFactory $layoutFactory,
        State $appState,
        UrlInterface $urlModel,
        Variables $configVariables,
        VariableResolverInterface $variableResolver,
        \Magento\Email\Model\Template\Css\Processor $cssProcessor,
        Filesystem $pubDirectory,
        CssInliner $cssInliner,
        $variables = [],
        array $directiveProcessors = []
    ) {
        $this->_coreRegistry = $registry;
        $this->tokenizeParams = $tokenizeParams;
        parent::__construct(
            $string,
            $logger,
            $escaper,
            $assetRepo,
            $scopeConfig,
            $coreVariableFactory,
            $storeManager,
            $layout,
            $layoutFactory,
            $appState,
            $urlModel,
            $configVariables,
            $variableResolver,
            $cssProcessor,
            $pubDirectory,
            $cssInliner,
            $variables,
            $directiveProcessors
        );
    }

    /**
     * @return \Vnecoms\Vendors\Model\Vendor
     */
    public function getVendor()
    {
        return $this->_coreRegistry->registry('current_vendor');
    }

    /**
     * @return \Vnecoms\VendorsPage\Helper\Data
     */
    public function getVendorHelper()
    {
        return \Magento\Framework\App\ObjectManager::getInstance()
            ->get('Vnecoms\VendorsPage\Helper\Data');
    }
}
