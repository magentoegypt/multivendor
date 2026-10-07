<?php
declare(strict_types=1);

namespace MagentoEgypt\AccountExtend\Model;

use Magento\Framework\Phrase;

/**
 * The one customer-facing sentence for an address whose domain cannot receive mail (CL036-TC97),
 * shared by every server-side guard and the storefront hint so they never disagree.
 */
class UndeliverableEmailMessage
{
    /**
     * @param array{domain: string, deliverable: bool|null, suggestion: string|null} $check
     */
    public static function from(array $check): Phrase
    {
        if (!empty($check['suggestion'])) {
            return __(
                'We can\'t deliver email to "%1". Did you mean %2?',
                $check['domain'],
                $check['suggestion']
            );
        }

        return __('We can\'t deliver email to "%1". Please check the address.', $check['domain']);
    }
}
