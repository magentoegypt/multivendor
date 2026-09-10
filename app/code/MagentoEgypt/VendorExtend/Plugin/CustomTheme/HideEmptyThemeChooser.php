<?php
/**
 * Don't ask anyone to choose a theme when there are none.
 *
 * `ves_vendor_theme` is empty on this store — no vendor themes have been
 * installed — and Vnecoms' chooser renders anyway: a "Select a Theme" heading
 * over a single nameless tile, "Default Theme", which is not a choice. It showed
 * up in two places:
 *
 *   * the seller registration form, under the fields ([CL036-DEV01.24]);
 *   * the seller panel's own settings, where the same list is the control for
 *     the `custom_theme/general/theme` field — the client's "empty theme tile on
 *     the vendor profile form".
 *
 * Both render through classes of their own, so both are answered here rather
 * than hidden per page in CSS: the register page's rule was a stopgap for one of
 * them, and the panel is a different area with a different stylesheet.
 *
 * SELF-HEALING, and that is the point of testing the collection rather than
 * deleting the field: install a vendor theme and the chooser returns, in both
 * places, with something in it.
 *
 * The config FIELD is suppressed at render(), which takes its label with it — an
 * empty control under a "Custom Theme" label would be its own small mystery.
 */
declare(strict_types=1);

namespace MagentoEgypt\VendorExtend\Plugin\CustomTheme;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Psr\Log\LoggerInterface;
use Vnecoms\VendorsCustomTheme\Block\Account\Create\ThemeList as StorefrontThemeList;
use Vnecoms\VendorsCustomTheme\Block\Adminhtml\System\Config\Form\Field\Theme as ThemeConfigField;
use Vnecoms\VendorsCustomTheme\Model\ResourceModel\Theme\CollectionFactory;
use Vnecoms\VendorsCustomTheme\Model\Theme;

class HideEmptyThemeChooser
{
    private ?bool $hasThemes = null;

    public function __construct(
        private readonly CollectionFactory $collectionFactory,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * The storefront chooser — seller registration, and the combined customer
     * registration when the store is configured that way.
     *
     * @param StorefrontThemeList $subject
     * @param string $result
     * @return string
     */
    public function afterToHtml(StorefrontThemeList $subject, $result)
    {
        return $this->hasThemes() ? $result : '';
    }

    /**
     * The seller panel's settings field, label and all.
     *
     * @param ThemeConfigField $subject
     * @param string $result
     * @param AbstractElement $element
     * @return string
     */
    public function afterRender(ThemeConfigField $subject, $result, AbstractElement $element)
    {
        return $this->hasThemes() ? $result : '';
    }

    private function hasThemes(): bool
    {
        if ($this->hasThemes === null) {
            try {
                $this->hasThemes = (bool) $this->collectionFactory->create()
                    ->addFieldToFilter('status', Theme::STATUS_ENABLE)
                    ->getSize();
            } catch (\Throwable $e) {
                /* If the question cannot be answered, leave the page as it was. */
                $this->logger->warning('Vendor theme chooser: ' . $e->getMessage());
                $this->hasThemes = true;
            }
        }

        return $this->hasThemes;
    }
}
