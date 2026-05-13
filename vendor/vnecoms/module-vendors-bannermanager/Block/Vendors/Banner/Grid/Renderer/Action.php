<?php

namespace Vnecoms\BannerManager\Block\Vendors\Banner\Grid\Renderer;

use Vnecoms\BannerManager\Block\Vendors\Banner\Grid\Renderer\Action\UrlBuilder;

/**
 * Class Action Grids
 * 
 * @category Vnecoms
 * @package  Vnecoms_BannerManager
 * @module   BannerManager
 * @author   Vnecoms Developer Team
 */
class Action extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{

    /** @var UrlBuilder  */
    protected $actionUrlBuilder;

    /**
     * Action constructor
     *
     * @param \Magento\Backend\Block\Context $context
     * @param UrlBuilder $actionUrlBuilder
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        UrlBuilder $actionUrlBuilder,
        array $data = []
    ) {
        $this->actionUrlBuilder = $actionUrlBuilder;
        parent::__construct($context, $data);
    }

    /**
     * Render action
     *
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $href = $this->actionUrlBuilder->getUrl(
            $row->getIdentifier(),
            $row->getData('_first_store_id'),
            $row->getStoreCode()
        );
        return '<a href="' . $href . '" target="_blank">' . __('Preview') . '</a>';
    }
}
