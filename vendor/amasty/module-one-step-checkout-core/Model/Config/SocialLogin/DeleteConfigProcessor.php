<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\Config\SocialLogin;

use Amasty\CheckoutCore\Model\Config;
use Magento\Config\Model\Config\Loader;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;

class DeleteConfigProcessor
{
    /**
     * @var WriterInterface
     */
    private $writer;
    
    /**
     * @var ReinitableConfigInterface
     */
    private $reinitableConfig;

    /**
     * @var Loader
     */
    private $loader;

    public function __construct(
        WriterInterface $writer,
        Loader $loader,
        ReinitableConfigInterface $reinitableConfig
    ) {
        $this->writer = $writer;
        $this->loader = $loader;
        $this->reinitableConfig = $reinitableConfig;
    }
    
    public function process(string $scope, int $scopeId): void
    {
        $loginConfigs = $this->loader->getConfigByPath('amsociallogin', $scope, $scopeId);
        //delete config value in OSC if it was deleted in Social Login
        if (!array_key_exists(Config::SOCIAL_LOGIN_POSITION_PATH, $loginConfigs)) {
            $this->writer->delete(
                Config::PATH_PREFIX . Config::ADDITIONAL_OPTIONS . Config::FIELD_SOCIAL_LOGIN,
                $scope,
                $scopeId
            );
            $this->reinitableConfig->reinit();
        }
    }
}
