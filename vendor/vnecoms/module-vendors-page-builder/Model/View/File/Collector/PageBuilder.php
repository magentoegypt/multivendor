<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Vnecoms\VendorsPageBuilder\Model\View\File\Collector;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Component\DirSearch;
use Magento\Framework\View\Design\ThemeInterface;
use Magento\Framework\View\File\CollectorInterface;
use Magento\Framework\View\File\Factory as FileFactory;

/**
 * Source of Page Builder view files introduced by modules
 */
class PageBuilder extends \Magento\PageBuilder\Model\View\File\Collector\PageBuilder implements CollectorInterface
{
    /**
     * @var DirSearch
     */
    private $componentDirSearch;

    /**
     * @var string
     */
    private $subDir;

    /**
     * @var FileFactory
     */
    private $fileFactory;

    /**
     * @var
     */
    private $vendorSession;

    /**
     * Constructor
     *
     * @param DirSearch $dirSearch
     * @param FileFactory $fileFactory
     * @param string $subDir
     */
    public function __construct(
        DirSearch $dirSearch,
        FileFactory $fileFactory,
        \Vnecoms\Vendors\Model\Session $vendorSession,
        $subDir = ''
    ) {
        $this->componentDirSearch = $dirSearch;
        $this->fileFactory = $fileFactory;
        $this->subDir = $subDir ? $subDir . '/' : '';
        $this->vendorSession = $vendorSession;
    }

    /**
     * Retrieve files, they're only contained within the adminhtml directory
     *
     * @param \Magento\Framework\View\Design\ThemeInterface $theme
     * @param string $filePath
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @return \Magento\Framework\View\File[]
     */
    public function getFiles(ThemeInterface $theme, $filePath)
    {
        $adminArea = "vendors";
        $result = [];
        $pageBuilderFiles = $this->componentDirSearch->collectFilesWithContext(
            ComponentRegistrar::MODULE,
            "view/{$adminArea}/{$this->subDir}{$filePath}"
        );
        foreach ($pageBuilderFiles as $file) {
            $result[] = $this->fileFactory->create($file->getFullPath(), $file->getComponentName(), null, true);
        }
        return $result;
    }
}
