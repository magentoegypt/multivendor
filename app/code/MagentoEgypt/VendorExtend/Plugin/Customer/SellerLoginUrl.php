<?php
/**
 * A seller asked to sign in should be shown the SELLER login.
 *
 * WHAT THE CLIENT SAW ([CL036-DEV01.24])
 * --------------------------------------
 * "The dashboard hyperlinks lead to the regular customer login. And also, when
 * we try to open it from the footer, it opens the customer login page."
 *
 * /marketplace/dashboard/ answered 302 to
 * /customer/account/login/referer/<the dashboard>, and the page that came up was
 * headed "Customer Login" — on a store that has a separate seller login at
 * /marketplace/seller/login/ and is configured to use it
 * (vendors/create_account/vendor_register_type = 1, "Use separated Seller
 * Login/Registration Page").
 *
 * WHY VNECOMS' OWN REDIRECT NEVER RUNS
 * ------------------------------------
 * Vnecoms\Vendors\App\AbstractAction::dispatch() does read that setting and
 * would send the visitor to marketplace/seller/login. It never gets the chance:
 * Vnecoms\Vendors\Controller\AbstractAction extends Magento's
 * Customer\Controller\AbstractAccount, and Magento registers
 * Customer\Controller\Plugin\Account on that class. The plugin runs BEFORE the
 * controller, calls Customer\Model\Session::authenticate(), and that redirects
 * to Customer\Model\Url::getLoginUrl() — the customer login — before any vendor
 * code is reached.
 *
 * SO THE FIX IS WHERE THAT REDIRECT IS BUILT
 * ------------------------------------------
 * Session::authenticate() does NOT call getLoginUrl(); it builds the URL itself
 * from ROUTE_ACCOUNT_LOGIN — which is why plugging getLoginUrl() alone changed
 * nothing, measured. It does, however, take a login URL as its argument and use
 * it verbatim when one is given. So inside the marketplace area this hands it
 * the seller login and lets core do everything else, including remembering where
 * the visitor was going.
 *
 * getLoginUrl() is plugged as well, so the "Sign In" links rendered ON vendor
 * pages agree with where the redirect sends people.
 *
 * Both are limited to the vendor area and to stores configured for a separate
 * seller login; every other "Sign In" on the storefront is untouched. The area
 * test accepts either the front name or the routed module name — the first is
 * what an unrouted request carries, the second is what a dispatched one does.
 */
declare(strict_types=1);

namespace MagentoEgypt\VendorExtend\Plugin\Customer;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;
use Vnecoms\Vendors\Helper\Data as VendorHelper;
use Vnecoms\Vendors\Model\Source\RegisterType;

class SellerLoginUrl
{
    /** The route Vnecoms registers for the whole vendor area. */
    private const VENDOR_FRONT_NAME = 'marketplace';

    /** The separate seller login, as Vnecoms' own dispatch() names it. */
    private const SELLER_LOGIN_PATH = 'marketplace/seller/login';

    public function __construct(
        private readonly HttpRequest $request,
        private readonly UrlInterface $urlBuilder,
        private readonly VendorHelper $vendorHelper,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * The redirect an unauthenticated vendor page performs.
     *
     * @param CustomerSession $subject
     * @param callable $proceed
     * @param string|null $loginUrl
     * @return bool
     */
    public function aroundAuthenticate(CustomerSession $subject, callable $proceed, $loginUrl = null)
    {
        if ($loginUrl === null) {
            $loginUrl = $this->sellerLoginUrl();
        }

        return $proceed($loginUrl);
    }

    /**
     * The "Sign In" links rendered on vendor pages.
     *
     * @param CustomerUrl $subject
     * @param string $result
     * @return string
     */
    public function afterGetLoginUrl(CustomerUrl $subject, $result)
    {
        return $this->sellerLoginUrl() ?? $result;
    }

    /**
     * The seller login, or null when this request is not the vendor area's.
     */
    private function sellerLoginUrl(): ?string
    {
        try {
            if (!$this->isVendorArea()) {
                return null;
            }

            if ((int) $this->vendorHelper->getSellerRegisterType() !== RegisterType::TYPE_SEPARATED) {
                /* Configured to share the customer account: core's URL is right. */
                return null;
            }

            return $this->urlBuilder->getUrl(self::SELLER_LOGIN_PATH);
        } catch (\Throwable $e) {
            /* Never leave a visitor with no login at all. */
            $this->logger->warning('Seller login URL: ' . $e->getMessage());

            return null;
        }
    }

    private function isVendorArea(): bool
    {
        return $this->request->getFrontName() === self::VENDOR_FRONT_NAME
            || $this->request->getModuleName() === self::VENDOR_FRONT_NAME;
    }
}
