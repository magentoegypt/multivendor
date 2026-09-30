<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppOrders\Model\Package;

/**
 * HmOrderPackageTrack.tracking_url: the carrier's public tracking page for one number, or null.
 *
 * The website gives a customer no working carrier link on this install. Its "Track this shipment"
 * popup asks the carrier through Vnecoms' Track::getNumberDetail(), which calls
 * getVendorTrackingInfo(), a method Magento's carriers do not have, so a DHL or UPS number answers
 * "No detail for number"; a "Custom Value" carrier only ever shows its title and number. So the
 * link is built here from the carrier code alone, and only for carriers whose public page takes the
 * number in the URL (etc/di.xml). Anything else, custom above all, has no link: the app shows the
 * carrier and the number to copy.
 */
class TrackingUrl
{
    /**
     * @param array<string, string> $templates carrier code => https URL with one %s for the number
     */
    public function __construct(
        private readonly array $templates = []
    ) {
    }

    /**
     * The tracking page of $number at $carrierCode, or null when none can be built.
     */
    public function forTrack(string $carrierCode, string $number): ?string
    {
        $template = $this->templates[strtolower(trim($carrierCode))] ?? null;
        $number = trim($number);
        if (!is_string($template) || $number === '' || substr_count($template, '%s') !== 1) {
            return null;
        }
        if (!str_starts_with($template, 'https://')) {
            return null;
        }

        //  str_replace, not sprintf: a template may carry other percent-encoded characters.
        return str_replace('%s', rawurlencode($number), $template);
    }
}
