<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

// @codingStandardsIgnoreFile

/**
 * Block Wysiwyg Plugin Config.
 *
 * @author      Ves Team <dev_team@vnecoms.com>
 */

namespace Vnecoms\VendorsCms\Model\Block;

class Config
{
    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    protected $_assetRepo;

    /**
     * @var \Vnecoms\Vendors\Model\UrlInterface
     */
    protected $_url;

    /**
     * @var \Magento\Framework\Math\Random
     */
    protected $mathRandom;

    public function __construct(
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Vnecoms\Vendors\Model\UrlInterface $url,
        \Magento\Framework\Math\Random $random
    ) {
        $this->_assetRepo = $assetRepo;
        $this->_url = $url;
        $this->mathRandom = $random;
    }

    /**
     * Prepare block wysiwyg config.
     *
     * @param \Magento\Framework\DataObject $config
     *
     * @return array
     */
    public function getBlockPluginSettings($config)
    {
        $blockConfig = [];
        $onclickParts = [
            'search' => ['html_id'],
            'subject' => 'VendorsCmsBlockPlugin.loadChooser(\''.
                $this->getBlocksWysiwygActionUrl().
                '\', \'{{html_id}}\');',
        ];
        $blockWysiwyg = [
            [
                'name' => 'blockwidget',
                'src' => $this->getWysiwygJsPluginSrc(),
                'options' => [
                    'title' => __('Insert Block...'),
                    'url' => $this->getBlocksWysiwygActionUrl(),
                    'onclick' => $onclickParts,
                    'class' => 'add-block plugin',
                ],
            ],
        ];
        $configPlugins = $config->getData('plugins');
        $blockConfig['plugins'] = array_merge($configPlugins, $blockWysiwyg);

        return $blockConfig;
    }

    /**
     * Return url to wysiwyg plugin.
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getWysiwygJsPluginSrc()
    {
        $editorPluginJs = 'Vnecoms_VendorsCms/js/lib/wysiwyg/tiny_mce/plugins/blockwidget/editor_plugin.js';
        return $this->_assetRepo->getUrl($editorPluginJs);
    }

    /**
     * Return url of action to get variables.
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getBlocksWysiwygActionUrl()
    {
        return $this->_url->getUrl('cms/wysiwyg_blocks/wysiwygPlugin',
            [
                'uniq_id' => $this->mathRandom->getUniqueHash('vblock')
            ]
        );
    }
}
