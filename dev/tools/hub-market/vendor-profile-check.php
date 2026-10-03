<?php
/**
 * Hub Market: regression check for the vendor app's profile rules (ClickUp TC73 14zb93nv6vw).
 *
 *   php8.4 dev/tools/hub-market/vendor-profile-check.php [customerId]
 *
 * Every write runs inside a database transaction that is rolled back, so nothing is saved. No OTP is sent and no
 * number's send limit is used: the send-OTP checks replace the WhatsApp send and the rate limit with stand-ins.
 * Exit code 0 = all passed, 1 = a check failed.
 *
 * The default seller is customer 81 (vendor V8S2, QA's test seller). Any approved seller works.
 *
 * What it covers:
 *  1. "Mobile number already exists." never fires for the account's own number, in any spelling
 *     (Otp::isMobileUsedByAnotherAccount, used by the customer-save check and the WhatsApp OTP checks).
 *  2. PUT /V1/vendors/me (VendorSelfService::updateMe):
 *     - saving the seller's own number in the app's +20 format succeeds;
 *     - changing the country without a region_id clears the stored region_id;
 *     - a region_id of another country is refused;
 *     - a region_id from the chosen country's list is kept.
 *  3. WhatsApp send-OTP (WhatsAppManagement::sendOtp) with a recording stand-in for the send and an open guard
 *     (nothing is sent, no hourly budget is used):
 *     - VENDOR_UPDATEMOB for the seller's own number with the seller's token goes ahead;
 *     - another account's number, or no token, or VENDOR_REGISTER, is "Mobile number already exists.".
 */
use Magento\Framework\App\Bootstrap;

require dirname(__DIR__, 3) . '/app/bootstrap.php';
$om = Bootstrap::create(BP, $_SERVER)->getObjectManager();
$om->get(\Magento\Framework\App\State::class)->setAreaCode('webapi_rest');
$om->get(\Magento\Framework\Config\ScopeInterface::class)->setCurrentScope('webapi_rest');

$customerId = (int) ($argv[1] ?? 81);
$db = $om->get(\Magento\Framework\App\ResourceConnection::class)->getConnection();
$otp = $om->get(\MagentoEgypt\SmsExtend\Helper\Otp::class);
$service = $om->get(\MagentoEgypt\VendorExtend\Api\VendorSelfServiceInterface::class);

$customer = $db->fetchRow('SELECT entity_id, mobilenumber FROM customer_entity WHERE entity_id = ?', [$customerId]);
$vendorId = (int) $db->fetchOne('SELECT vendor_id FROM ves_vendor_user WHERE customer_id = ?', [$customerId]);
if (!$customer || !$vendorId || !$customer['mobilenumber']) {
    fwrite(STDERR, "Customer $customerId is not a seller with a mobile number.\n");
    exit(1);
}
$mobile = (string) $customer['mobilenumber'];
$national = substr(preg_replace('/\D+/', '', $mobile), -10);
$failures = 0;

$check = function (string $label, bool $ok, string $detail = '') use (&$failures) {
    printf("%s %s%s\n", $ok ? 'PASS' : 'FAIL', $label, $detail !== '' ? "  ($detail)" : '');
    $failures += $ok ? 0 : 1;
};

/* Runs $fn inside a transaction that is always rolled back; returns [result|null, exception|null]. */
$inRollback = function (callable $fn) use ($db) {
    $db->beginTransaction();
    try {
        return [$fn(), null];
    } catch (\Throwable $e) {
        return [null, $e];
    } finally {
        $db->rollBack();
    }
};

$vendorData = function (array $fields) use ($om) {
    $vendor = $om->create(\Vnecoms\VendorsApi\Api\Data\VendorInterface::class);
    foreach ($fields as $key => $value) {
        $vendor->setData($key, $value);
    }
    return $vendor;
};

$regionOf = function (string $country) use ($db) {
    return (int) $db->fetchOne('SELECT region_id FROM directory_country_region WHERE country_id = ? ORDER BY region_id LIMIT 1', [$country]);
};

// 1. The account's own number is never "another account's".
foreach (array_unique([$mobile, '+20' . $national, '0' . $national, $national]) as $spelling) {
    $check(
        "own number \"$spelling\" is not taken for the account itself",
        !$otp->isMobileUsedByAnotherAccount($spelling, $customerId)
    );
}
$check('the same number counts as taken for a NEW account', $otp->isMobileUsedByAnotherAccount($mobile, null));

// 2a. Saving the own number in the app's +20 format.
[, $e] = $inRollback(fn() => $service->updateMe($customerId, $vendorData(['telephone' => '+20' . $national])));
$check('PUT /vendors/me with the own number (+20…) succeeds', $e === null, $e ? $e->getMessage() : '');

// 2b-2d. Country and region.
$current = $db->fetchRow('SELECT country_id, region_id FROM ves_vendor_entity WHERE entity_id = ?', [$vendorId]);
$other = $current['country_id'] === 'EG' ? 'AL' : 'EG';
$foreignRegion = $regionOf($current['country_id'] === 'IS' ? 'AL' : 'IS');
$otherRegion = $regionOf($other);

[$regionAfter, $e] = $inRollback(function () use ($service, $customerId, $vendorData, $other, $db, $vendorId) {
    $service->updateMe($customerId, $vendorData(['country_id' => $other, 'region' => 'Free text']));
    return $db->fetchOne('SELECT region_id FROM ves_vendor_entity WHERE entity_id = ?', [$vendorId]);
});
$check("country {$current['country_id']} → $other without region_id clears region_id",
    $e === null && ($regionAfter === null || (int) $regionAfter === 0),
    $e ? $e->getMessage() : 'region_id now ' . var_export($regionAfter, true));

[, $e] = $inRollback(fn() => $service->updateMe($customerId, $vendorData(['country_id' => $other, 'region_id' => $foreignRegion])));
$check("region_id $foreignRegion of another country is refused for $other",
    $e instanceof \Magento\Framework\Exception\InputException, $e ? $e->getMessage() : 'no error');

// A region from the country's list is kept. When the other country has no list (EG has none here), use the
// seller's own country and a region other than the stored one, so the save is a real change.
$listCountry = $otherRegion ? $other : $current['country_id'];
$listRegion = $otherRegion ?: (int) $db->fetchOne(
    'SELECT region_id FROM directory_country_region WHERE country_id = ? AND region_id <> ? ORDER BY region_id LIMIT 1',
    [$current['country_id'], (int) $current['region_id']]
);
if ($listRegion) {
    [$kept, $e] = $inRollback(function () use ($service, $customerId, $vendorData, $listCountry, $listRegion, $db, $vendorId) {
        $service->updateMe($customerId, $vendorData(['country_id' => $listCountry, 'region_id' => $listRegion, 'region' => 'From the list']));
        return (int) $db->fetchOne('SELECT region_id FROM ves_vendor_entity WHERE entity_id = ?', [$vendorId]);
    });
    $check("region_id $listRegion of $listCountry is kept", $e === null && $kept === $listRegion, $e ? $e->getMessage() : "region_id $kept");
} else {
    echo "SKIP a region from the list is kept (neither $other nor {$current['country_id']} has a region list)\n";
}

// 3. WhatsApp send-OTP (POST /V1/whatsapp/otp/send), where QA's "Mobile number already exists." for the seller's own
//    number came from. sendOtp() runs with a stand-in for the WhatsApp send (it only records the number) and a guard
//    that allows every request, so no message goes out and no number's hourly send budget is used.
$recorded = [];
$recordingOtp = new class($otp, $recorded) extends \MagentoEgypt\SmsExtend\Helper\Otp {
    private $real;
    private $recorded;

    public function __construct($real, array &$recorded)
    {
        $this->real = $real;
        $this->recorded = &$recorded;
    }

    public function isMobileUsedByAnotherAccount($mobile, $customerId = null, $websiteId = null)
    {
        return $this->real->isMobileUsedByAnotherAccount($mobile, $customerId, $websiteId);
    }

    public function sendOtp($mobileNum)
    {
        $this->recorded[] = (string) $mobileNum;
        return true;
    }
};
$openGuard = new class extends \MagentoEgypt\SmsExtend\Model\Otp\OtpGuard {
    public function __construct()
    {
    }

    public function sendWait(string $mobile, string $clientIp, ?int $now = null): int
    {
        return 0;
    }
};
$caller = new class implements \Magento\Authorization\Model\UserContextInterface {
    public $customerId = null;

    public function getUserId()
    {
        return $this->customerId;
    }

    public function getUserType()
    {
        return $this->customerId ? self::USER_TYPE_CUSTOMER : self::USER_TYPE_GUEST;
    }
};
$whatsApp = $om->create(\MagentoEgypt\SmsExtend\Model\WhatsAppManagement::class, [
    'whatsAppHelper' => $recordingOtp,
    'userContext' => $caller,
    'guard' => $openGuard,
]);
$sendOtp = function (?int $asCustomer, string $number, string $type) use ($whatsApp, $caller, &$recorded) {
    $caller->customerId = $asCustomer;
    $recorded = [];
    $result = $whatsApp->sendOtp($number, $type);
    return [(string) $result->getData('status'), (string) $result->getData('message'), $recorded];
};
$alreadyExists = (string) __('Mobile number already exists.');
$ownNumber = '+20' . $national;

[$status, $message, $sent] = $sendOtp($customerId, $ownNumber, 'VENDOR_UPDATEMOB');
$check("send-OTP VENDOR_UPDATEMOB for the seller's own number, with the seller's token, is not \"already exists\"",
    $status === 'success' && $sent === [$ownNumber], "$status: $message; only recorded by the stand-in, nothing was sent");

$otherMobile = (string) $db->fetchOne(
    "SELECT mobilenumber FROM customer_entity WHERE entity_id <> ? AND TRIM(COALESCE(mobilenumber, '')) <> '' ORDER BY entity_id LIMIT 1",
    [$customerId]
);
if ($otherMobile !== '') {
    [$status, $message, $sent] = $sendOtp($customerId, $otherMobile, 'VENDOR_UPDATEMOB');
    $check("send-OTP VENDOR_UPDATEMOB for another account's number ($otherMobile) is refused",
        $status === 'error' && $message === $alreadyExists && $sent === [], "$status: $message");
}

[$status, $message, $sent] = $sendOtp(null, $ownNumber, 'VENDOR_UPDATEMOB');
$check('send-OTP VENDOR_UPDATEMOB for that number without a token is refused (no caller to exclude)',
    $status === 'error' && $message === $alreadyExists && $sent === [], "$status: $message");

[$status, $message, $sent] = $sendOtp($customerId, $ownNumber, 'VENDOR_REGISTER');
$check('send-OTP VENDOR_REGISTER never excludes the caller',
    $status === 'error' && $message === $alreadyExists && $sent === [], "$status: $message");

$unchanged = $db->fetchRow('SELECT country_id, region_id FROM ves_vendor_entity WHERE entity_id = ?', [$vendorId]);
$check('nothing was saved (rolled back)', $unchanged == $current, json_encode($unchanged));

echo $failures ? "\n$failures check(s) FAILED\n" : "\nAll checks passed\n";
exit($failures ? 1 : 0);
