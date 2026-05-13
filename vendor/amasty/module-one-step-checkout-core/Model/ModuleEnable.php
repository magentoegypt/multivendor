<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model;

/**
 * Class for check Extensions enable status.
 * Modules with additional compatibilities
 */
class ModuleEnable
{
    public const TIG_POSTNL_MODULE_NAMESPACE = 'TIG_PostNL';
    public const MODULE_ORDER_ATTRIBUTES = 'Amasty_Orderattr';
    public const MODULE_CUSTOMER_ATTRIBUTES = 'Amasty_CustomerAttributes';
    public const MODULE_GDPR = 'Amasty_Gdpr';
    public const MODULE_SOCIAL_LOGIN = 'Amasty_SocialLogin';
    public const MODULE_AMAZON_PAY_V2 = 'Amazon_Pay';

    /**
     * @var \Magento\Framework\Module\Manager
     */
    private $moduleManager;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    public function __construct(
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->moduleManager = $moduleManager;
        $this->objectManager = $objectManager;
    }

    /**
     * @return bool
     */
    public function isPostNlEnable()
    {
        return $this->moduleManager->isEnabled(self::TIG_POSTNL_MODULE_NAMESPACE);
    }

    /**
     * @return bool
     */
    public function isOrderAttributesEnable()
    {
        return $this->moduleManager->isEnabled(self::MODULE_ORDER_ATTRIBUTES);
    }

    /**
     * @return bool
     */
    public function isCustomerAttributesEnable()
    {
        return $this->moduleManager->isEnabled(self::MODULE_CUSTOMER_ATTRIBUTES);
    }

    /**
     * @return bool
     */
    public function isAmazonPayV2Enable(): bool
    {
        return $this->moduleManager->isEnabled(self::MODULE_AMAZON_PAY_V2);
    }

    /**
     * @return bool
     */
    public function isGdprEnable(): bool
    {
        return $this->moduleManager->isEnabled(self::MODULE_GDPR);
    }

    /**
     * @return bool
     */
    public function isSocialLoginEnable(): bool
    {
        return $this->moduleManager->isEnabled(self::MODULE_SOCIAL_LOGIN);
    }

    /**
     * @return bool
     */
    public function isGdprVisibleOnCheckout(): bool
    {
        if (class_exists(\Amasty\Gdpr\Model\Checkbox::class)) {
            return $this->objectManager->get(\Amasty\Gdpr\Model\Checkbox::class)
                ->isVisible(\Amasty\Gdpr\Model\Checkbox::AREA_CHECKOUT);
        }

        return (bool)$this->objectManager->get(\Amasty\Gdpr\Model\Consent\DataProvider\FrontendData::class)
            ->getData(\Amasty\Gdpr\Model\ConsentLogger::FROM_CHECKOUT);
    }
}
