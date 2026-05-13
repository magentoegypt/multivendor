<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\BannerManager\Model\Template;

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

class BannerFilter extends \Magento\Email\Model\Template\Filter
{
    protected $_banner;

    /**
     * @var \Magento\Framework\Filter\Template\Tokenizer\Parameter
     */
    protected $tokenizeParams;

    /**
     * BannerFilter constructor.
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

    /***
     * @return \Vnecoms\BannerManager\Model\Banner
     */
    public function getBanner()
    {
        if (!$this->_banner) {
            $this->_banner = \Magento\Framework\App\ObjectManager::getInstance()
                ->create('Vnecoms\BannerManager\Model\Banner');
        }

        return $this->_banner;
    }

    /**
     * Retrieve vendor block directive.
     *
     * @param array $construction
     *
     * @return string
     */
    public function bannerDirective($construction)
    {
        $skipParams = array('id');
        $this->tokenizeParams->setString($construction[2]);
        $bannerParameters = $this->tokenizeParams->tokenize();
        //var_dump($bannerParameters);die;
        $layout = \Magento\Framework\App\ObjectManager::getInstance()->create('Magento\Framework\View\LayoutInterface');

        $banner = $layout->createBlock('Vnecoms\BannerManager\Block\Banner');
        if ($bannerParameters['banner_id'] && $banner) {
            $banner->setBannerFilterId($bannerParameters['banner_id']);
        }
        if ($bannerParameters['banner_id'] && $banner) {
            $banner->setBannerParams($bannerParameters);
            foreach ($bannerParameters as $k => $v) {
                if (in_array($k, $skipParams)) {
                    continue;
                }
                $banner->setDataUsingMethod($k, $v);
            }
        }
        if (!$banner) {
            return '';
        }

        return $banner->toHtml();
    }
}


