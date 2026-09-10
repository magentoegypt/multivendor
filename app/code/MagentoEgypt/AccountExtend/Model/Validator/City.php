<?php
/**
 * "Invalid City. Please use A-Z, a-z, 0-9, -, ', spaces" — for a city that
 * contains a digit.
 *
 * WHAT THE MESSAGE PROMISES AND WHAT THE CODE DOES
 * ------------------------------------------------
 * Magento\Customer\Model\Validator\City matches
 *
 *     /(?:[\p{L}\p{M}\s\-\']{1,100})/u
 *
 * against the value and then insists the match is the WHOLE value. That set has
 * no digits in it, while the message it prints when the check fails lists 0-9
 * as allowed. Measured against city names customers on an Egyptian store
 * actually type:
 *
 *     New York                    passes
 *     القاهرة                     passes   (Arabic letters are \p{L} — never the problem)
 *     6th of October City         FAILS    (matched "th of October City")
 *     10th of Ramadan             FAILS
 *     15 May City                 FAILS
 *     ٦ أكتوبر                    FAILS    (Arabic-Indic digits)
 *     New Cairo, 5th Settlement   FAILS
 *     Giza (Dokki)                FAILS
 *     Port Said/Suez              FAILS
 *
 * Three of Egypt's largest cities are named after dates. Two customers on this
 * store hit this while saving an address, and the checkout showed them a message
 * naming digits as permitted.
 *
 * WHAT THIS ALLOWS
 * ----------------
 * Letters and marks in any script, NUMBERS in any script (`\p{N}`, so the
 * Arabic-Indic digits count as well as 0-9), whitespace, and the punctuation
 * that appears in real place names: hyphen, both apostrophes, comma, full stop,
 * slash, parentheses and ampersand. The same shape Magento's own STREET
 * validator already allows — street numbers were never in doubt.
 *
 * Anchored rather than core's "longest run must equal the input" trick, which
 * says the same thing more plainly. The empty case is left alone: whether a city
 * is required is decided elsewhere, and this validator has always passed it.
 */
declare(strict_types=1);

namespace MagentoEgypt\AccountExtend\Model\Validator;

use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Validator\City as CoreCity;

class City extends CoreCity
{
    /**
     * Letters, marks, numbers (any script), spaces, and place-name punctuation.
     */
    private const PATTERN_CITY = '/^[\p{L}\p{M}\p{N}\s\-\'’,.\/()&]{1,100}$/u';

    /**
     * @param Customer $customer
     * @return bool
     */
    public function isValid($customer)
    {
        $this->_clearMessages();

        $city = $customer->getCity();

        if ($city !== null && $city !== '' && !preg_match(self::PATTERN_CITY, (string) $city)) {
            $this->_addMessages([[
                'city' => (string) __(
                    'Invalid City. Please use letters, numbers, spaces and - \' , . / ( ) &'
                ),
            ]]);
        }

        return count($this->_messages) === 0;
    }
}
