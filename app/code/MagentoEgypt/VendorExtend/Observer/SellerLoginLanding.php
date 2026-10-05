<?php
/**
 * Where a seller lands after signing in on the CUSTOMER login page.
 *
 * WHAT QA REPORTED ([CL036-DEV01.44], 14zb93nvwqg)
 * -----------------------------------------------
 * "Logging in incorrectly redirects the seller to the standard customer
 * dashboard (/customer/account/) instead of the Marketplace Seller Dashboard."
 *
 * Reproduced 2026-10-05, headless, through the real WhatsApp-OTP sign-in:
 *   - the seller login page (/marketplace/seller/login/, the footer's "Seller
 *     Dashboard") already lands a seller on /vendors/dashboard/, EN and AR;
 *   - the customer login page (/customer/account/login/, the header's "Login")
 *     landed the same seller on /customer/account/.
 *
 * WHY
 * ---
 * After the OTP is verified, Vnecoms_Sms' validate-customer.js does
 * window.location.reload(). The reloaded customer login page meets a logged-in
 * visitor and Magento\Customer\Controller\Account\Login sends every one of them
 * to its own index, the customer dashboard, whoever they are and whatever
 * `referer` the URL carried.
 *
 * WHAT THIS DOES
 * --------------
 * Before that controller runs, and only for an APPROVED seller who is already
 * signed in (i.e. that reload):
 *   - the sign-in was prompted by a page (the URL carries Magento's `referer`,
 *     e.g. /customer/account/login/referer/<base64>/ from "My Account", the
 *     wishlist or order history): back to that page, same origin only;
 *   - otherwise: the seller dashboard, the same URL the seller login page uses.
 * Customers who are not sellers, and sellers whose account is not approved, keep
 * Magento's behaviour. Sign-in popups reload the page they were opened on and
 * never reach this page, so checkout and the header popup are unaffected.
 *
 * No constructor on purpose: production runs compiled DI, and a new class with
 * no constructor arguments needs no setup:di:compile.
 */
declare(strict_types=1);

namespace MagentoEgypt\VendorExtend\Observer;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class SellerLoginLanding implements ObserverInterface
{
    public function execute(Observer $observer): void
    {
        try {
            $om = ObjectManager::getInstance();

            /** @var \Magento\Customer\Model\Session $customerSession */
            $customerSession = $om->get(\Magento\Customer\Model\Session::class);
            if (!$customerSession->isLoggedIn()) {
                return;
            }

            $vendor = $om->get(\Vnecoms\Vendors\Model\Session::class)->getVendor();
            if (!$vendor || !$vendor->getId()
                || (int) $vendor->getStatus() !== \Vnecoms\Vendors\Model\Vendor::STATUS_APPROVED
            ) {
                return;
            }

            $controller = $observer->getEvent()->getControllerAction();
            $request = $observer->getEvent()->getRequest() ?: $controller->getRequest();

            $target = $this->refererUrl((string) $request->getParam('referer'))
                ?? $om->get(\Vnecoms\Vendors\Helper\Data::class)->getUrl('dashboard');

            $controller->getResponse()->setRedirect($target);
            $om->get(\Magento\Framework\App\ActionFlag::class)->set('', ActionInterface::FLAG_NO_DISPATCH, true);
        } catch (\Throwable $e) {
            // Magento's own redirect (the customer dashboard) is the fallback, never an error page.
            ObjectManager::getInstance()->get(\Psr\Log\LoggerInterface::class)
                ->warning('Seller login landing skipped: ' . $e->getMessage());
        }
    }

    /**
     * The page that asked for the sign-in, if it is one of ours and not a login page itself.
     */
    private function refererUrl(string $encoded): ?string
    {
        if ($encoded === '') {
            return null;
        }

        $om = ObjectManager::getInstance();
        $url = (string) $om->get(\Magento\Framework\Url\DecoderInterface::class)->decode($encoded);
        if ($url === ''
            || !$om->get(\Magento\Framework\Url\HostChecker::class)->isOwnOrigin($url)
            || preg_match('#/customer/account/(login|loginPost|logout|create)\b#', $url)
        ) {
            return null;
        }

        return $url;
    }
}
