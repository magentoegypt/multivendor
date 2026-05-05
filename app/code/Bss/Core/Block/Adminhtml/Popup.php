<?php
/**
 * BSS Commerce Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://bsscommerce.com/Bss-Commerce-License.txt
 *
 * @category   BSS
 * @package    Bss_Core
 * @author     Extension Team
 * @copyright  Copyright (c) 2017-2018 BSS Commerce Co. ( http://bsscommerce.com )
 * @license    http://bsscommerce.com/Bss-Commerce-License.txt
 */

namespace Bss\Core\Block\Adminhtml;

use Magento\Framework\View\Element\Template;

/**
 * Class Popup
 * @package Bss\Core\Block\Adminhtml
 */
class Popup extends Template
{
    /**
     * @var \Bss\Core\Helper\Module
     */
    private $moduleHelper;

    /**
     * @var \Bss\Core\Helper\Data
     */
    private $bssHelper;

    /**
     * @var \Bss\Core\Helper\Api
     */
    private $apiHelper;

    /**
     * @var Header
     */
    private $headerBlock;

    /**
     * Popup constructor.
     * @param Template\Context $context
     * @param \Bss\Core\Helper\Data $bssHelper
     * @param \Bss\Core\Helper\Module $moduleHelper
     * @param \Bss\Core\Helper\Api $apiHelper
     * @param Header $headerBlock
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        \Bss\Core\Helper\Data $bssHelper,
        \Bss\Core\Helper\Module $moduleHelper,
        \Bss\Core\Helper\Api $apiHelper,
        Header $headerBlock,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->moduleHelper = $moduleHelper;
        $this->bssHelper = $bssHelper;
        $this->apiHelper = $apiHelper;
        $this->headerBlock = $headerBlock;
    }

    /**
     * @return array|null
     */
    public function getModuleHasNewVersion()
    {
        if ($this->bssHelper->isEnablePopup()) {
            return $this->moduleHelper->getListNewModuleVersion();
        }
        return null;
    }

    /**
     * @return array
     */
    public function getPopupConfig()
    {
        return $this->apiHelper->getConfigs();
    }
}
