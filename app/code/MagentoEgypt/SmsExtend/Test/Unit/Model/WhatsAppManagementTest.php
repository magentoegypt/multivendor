<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\SmsExtend\Test\Unit\Model;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Customer\Model\AuthenticationInterface;
use Magento\Framework\DataObject;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use MagentoEgypt\SmsExtend\Model\Otp\OtpGuard;
use MagentoEgypt\SmsExtend\Model\Throttle;
use MagentoEgypt\SmsExtend\Model\WhatsAppManagement;
use MagentoEgypt\SmsExtend\Test\Unit\ConfigStub;
use MagentoEgypt\SmsExtend\Test\Unit\MemoryCache;
use MagentoEgypt\SmsExtend\Test\Unit\OtpWithMemoryCache;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * POST /V1/whatsapp/otp/send and /verify (the seller app; the app's GraphQL sign-in verifies through it too),
 * with the real OTP helper code path on an in-memory cache, the real limits, and a fake account lookup,
 * sender and account lock. Nothing is ever sent.
 */
class WhatsAppManagementTest extends TestCase
{
    private const WRONG = 'That code is incorrect or has expired. Check it, or ask for a new code.';
    private const LOCKED = 'Too many incorrect codes. Please try again in 15 minutes.';

    private RestOtpHelper $otp;

    private RecordingAuthentication $authentication;

    private FixedAddress $address;

    private ConfigStub $config;

    private MemoryCache $cache;

    protected function setUp(): void
    {
        $this->otp = new RestOtpHelper();
        //  Customer 31 (UAE, stored in national form), 40 and 41 (the same Egyptian number twice).
        $this->otp->accounts = [31 => '0501234567', 40 => '+201001234567', 41 => '01001234567'];
        $this->authentication = new RecordingAuthentication();
        $this->address = new FixedAddress('10.0.0.1');
        $this->config = new ConfigStub();
        $this->cache = new MemoryCache();
    }

    private function service(): WhatsAppManagement
    {
        return new WhatsAppManagement(
            $this->otp,
            $this->authentication,
            new GuestContext(),
            new NullLogger(),
            new OtpGuard(new Throttle($this->cache), $this->cache, $this->config),
            $this->address
        );
    }

    private function send(string $mobile, string $type): DataObject
    {
        return $this->service()->sendOtp($mobile, $type);
    }

    private function verify(string $mobile, string $code, string $type, string $password = ''): DataObject
    {
        return $this->service()->verifyOtp($mobile, $code, $type, $password);
    }

    private static function text(DataObject $answer): string
    {
        return (string) $answer->getData('message');
    }

    /**
     * A six-digit code that is not the one filed for this number.
     */
    private function wrongCodeFor(string $number): string
    {
        return ($this->otp->sent[$number] ?? '') === '111111' ? '222222' : '111111';
    }

    public function testATypedSpellingSignsInWithTheCodeSentToTheStoredNumber(): void
    {
        $sent = $this->send('+971 50 123 4567', WhatsAppManagement::VENDOR_LOGIN);
        self::assertSame('success', $sent->getData('status'));
        self::assertSame(['+971501234567'], array_keys($this->otp->sent), 'the stored number, in international form');

        $code = $this->otp->sent['+971501234567'];
        $result = $this->verify('+971 50 123 4567', $code, WhatsAppManagement::VENDOR_LOGIN);
        self::assertSame('success', $result->getData('status'));
        self::assertSame('token-31', $result->getData('token'));
        self::assertSame([31], $this->authentication->unlocked, 'a right code still clears the account lock');
    }

    public function testNoAccountSeveralAccountsAndWrongCodesGetOneAnswer(): void
    {
        $this->send('0501234567', WhatsAppManagement::LOGIN);
        $wrong = $this->wrongCodeFor('+971501234567');

        $answers = [
            'no account' => $this->verify('+971509999999', '123456', WhatsAppManagement::LOGIN),
            'several accounts' => $this->verify('01001234567', '123456', WhatsAppManagement::FORGOTPASS, 'Secret123!'),
            'no code sent' => $this->verify('+971501234567', '123456', WhatsAppManagement::VENDOR_FORGOTPASS),
            'wrong code' => $this->verify('0501234567', $wrong, WhatsAppManagement::VENDOR_LOGIN),
        ];
        foreach ($answers as $case => $answer) {
            self::assertSame('error', $answer->getData('status'), $case);
            self::assertSame(self::WRONG, self::text($answer), $case);
            self::assertSame('', $answer->getData('token'), $case);
        }
        self::assertSame([], $this->otp->passwords);
    }

    public function testWrongCodesNeverTouchTheAccountLock(): void
    {
        for ($i = 0; $i < 12; $i++) {
            $this->verify('0501234567', '000000', WhatsAppManagement::LOGIN);
        }

        self::assertSame([], $this->authentication->failures);
        self::assertSame([], $this->authentication->lockChecks);
    }

    public function testFiveWrongCodesLockCodeSignInForThatNumberOnly(): void
    {
        $this->send('0501234567', WhatsAppManagement::LOGIN);
        $code = $this->otp->sent['+971501234567'];

        for ($i = 1; $i <= 4; $i++) {
            self::assertSame(self::WRONG, self::text($this->verify('0501234567', '000000', WhatsAppManagement::LOGIN)));
        }
        $fifth = $this->verify('+971501234567', '000000', WhatsAppManagement::LOGIN);
        self::assertSame(self::LOCKED, self::text($fifth));
        self::assertSame(900, $fifth->getData(WhatsAppManagement::RETRY_AFTER));

        //  Locked, whatever the code and whatever the spelling; the account itself is not.
        $blocked = $this->verify('501234567', $code, WhatsAppManagement::LOGIN);
        self::assertSame('error', $blocked->getData('status'));
        self::assertSame(self::LOCKED, self::text($blocked));
        self::assertSame([], $this->authentication->failures);
        //  Another number is not locked.
        self::assertSame(self::WRONG, self::text($this->verify('01001234567', '000000', WhatsAppManagement::LOGIN)));
    }

    public function testANumberWithoutAnAccountLocksAlikeSoTheLockTellsNothing(): void
    {
        for ($i = 1; $i <= 4; $i++) {
            $this->verify('+971509999999', '000000', WhatsAppManagement::LOGIN);
        }

        self::assertSame(self::LOCKED, self::text($this->verify('+971509999999', '000000', WhatsAppManagement::LOGIN)));
    }

    public function testARightCodeClearsTheWrongOnes(): void
    {
        $this->send('0501234567', WhatsAppManagement::LOGIN);
        for ($i = 1; $i <= 4; $i++) {
            $this->verify('0501234567', '000000', WhatsAppManagement::LOGIN);
        }
        $right = $this->verify('0501234567', $this->otp->sent['+971501234567'], WhatsAppManagement::LOGIN);
        self::assertSame('success', $right->getData('status'));

        for ($i = 1; $i <= 4; $i++) {
            self::assertSame(self::WRONG, self::text($this->verify('0501234567', '000000', WhatsAppManagement::LOGIN)));
        }
    }

    public function testEachAddressHasABudgetOfWrongCodes(): void
    {
        $this->config->values = [OtpGuard::XML_WRONG_CODES_IP => '3'];
        foreach (['+971501111111', '+971502222222', '+971503333333'] as $number) {
            $this->verify($number, '000000', WhatsAppManagement::LOGIN);
        }

        $refused = $this->verify('+971504444444', '000000', WhatsAppManagement::LOGIN);
        self::assertSame('error', $refused->getData('status'));
        self::assertStringStartsWith('Too many incorrect codes.', self::text($refused));
        $this->address->address = '10.0.0.2';
        self::assertSame(self::WRONG, self::text($this->verify('+971504444444', '000000', WhatsAppManagement::LOGIN)));
    }

    public function testSendLimitsCoverEveryRestFlowAndEveryNumber(): void
    {
        $this->config->values = [OtpGuard::XML_SEND_LIMIT_NUMBER => '2', OtpGuard::XML_SEND_LIMIT_IP => '100'];

        self::assertSame('success', $this->send('0501234567', WhatsAppManagement::VENDOR_LOGIN)->getData('status'));
        self::assertSame('success', $this->send('+971501234567', WhatsAppManagement::FORGOTPASS)->getData('status'));
        $third = $this->send('501234567', WhatsAppManagement::LOGIN);
        self::assertSame('error', $third->getData('status'));
        self::assertMatchesRegularExpression(
            '/^Too many code requests\. Please try again in ([1-9]|[1-5]\d|60) minutes\.$/',
            self::text($third)
        );

        //  A number without an account is counted the same, so the limit tells nothing.
        $this->send('+971509999999', WhatsAppManagement::LOGIN);
        $this->send('+971509999999', WhatsAppManagement::LOGIN);
        self::assertSame('error', $this->send('+971509999999', WhatsAppManagement::LOGIN)->getData('status'));

        //  Registration codes are paid messages too.
        $this->send('+971507777777', WhatsAppManagement::VENDOR_REGISTER);
        $this->send('+971507777777', WhatsAppManagement::VENDOR_REGISTER);
        self::assertSame('error', $this->send('+971507777777', WhatsAppManagement::VENDOR_REGISTER)->getData('status'));
        self::assertSame(['+971501234567', '+971501234567', '+971507777777', '+971507777777'], $this->otp->sendLog);
    }

    public function testThePerAddressSendLimit(): void
    {
        $this->config->values = [OtpGuard::XML_SEND_LIMIT_IP => '2'];

        $this->send('+971501111111', WhatsAppManagement::LOGIN);
        $this->send('+971502222222', WhatsAppManagement::REGISTER);
        self::assertSame('error', $this->send('+971503333333', WhatsAppManagement::LOGIN)->getData('status'));
        $this->address->address = '10.0.0.2';
        self::assertSame('success', $this->send('+971503333333', WhatsAppManagement::LOGIN)->getData('status'));
    }

    public function testRegistrationStillSaysTheNumberExists(): void
    {
        //  Kept: registration cannot avoid telling that a number is taken.
        $answer = $this->send('+971501234567', WhatsAppManagement::REGISTER);

        self::assertSame('error', $answer->getData('status'));
        self::assertSame('Mobile number already exists.', self::text($answer));
        self::assertSame([], $this->otp->sendLog);
    }

    public function testRegistrationKeepsItsMessagesAndCountsWrongCodes(): void
    {
        $this->send('+971508888888', WhatsAppManagement::VENDOR_REGISTER);
        $wrong = $this->wrongCodeFor('+971508888888');

        $first = $this->verify('+971508888888', $wrong, WhatsAppManagement::VENDOR_REGISTER);
        self::assertSame('Invalid OTP.', self::text($first));
        for ($i = 2; $i <= 4; $i++) {
            $this->verify('+971508888888', '000000', WhatsAppManagement::VENDOR_REGISTER);
        }
        $fifth = $this->verify('+971508888888', '000000', WhatsAppManagement::VENDOR_REGISTER);
        self::assertSame(self::LOCKED, self::text($fifth));
    }

    public function testForgotPasswordNeedsTheRightCode(): void
    {
        $this->send('0501234567', WhatsAppManagement::VENDOR_FORGOTPASS);

        $this->verify('0501234567', '000000', WhatsAppManagement::VENDOR_FORGOTPASS, 'Secret123!');
        self::assertSame([], $this->otp->passwords);

        $code = $this->otp->sent['+971501234567'];
        $done = $this->verify('0501234567', $code, WhatsAppManagement::VENDOR_FORGOTPASS, 'Secret123!');
        self::assertSame('success', $done->getData('status'));
        self::assertSame([31 => 'Secret123!'], $this->otp->passwords);
        self::assertSame([31], $this->authentication->unlocked);
    }

    public function testAnUnknownTypeIsRefusedWithoutCounting(): void
    {
        $answer = $this->send('+971501234567', 'SOMETHING');

        self::assertSame('error', $answer->getData('status'));
        self::assertSame('Invalid input data.', self::text($answer));
        self::assertSame([], $this->cache->entries);
    }
}

/**
 * The OTP helper with the real codes and keys (in-memory cache), accounts from an array matched with the
 * real candidates, and a sender that records instead of sending.
 */
class RestOtpHelper extends OtpWithMemoryCache
{
    /** @var array<int, string> customer id => stored mobilenumber */
    public array $accounts = [];

    /** @var array<string, string> number a code went to => that code */
    public array $sent = [];

    /** @var string[] every number a code went to, in order */
    public array $sendLog = [];

    /** @var array<int, string> customer id => new password */
    public array $passwords = [];

    public function getCustomersByMobile($input)
    {
        $candidates = $this->normalizeMobileCandidates($input);
        $out = [];
        foreach ($this->accounts as $id => $stored) {
            if (in_array($stored, $candidates, true)) {
                $out[] = new DataObject(['id' => $id, 'mobilenumber' => $stored]);
            }
        }

        return $out;
    }

    public function isMobileUsedByAnotherAccount($mobile, $customerId = null, $websiteId = null)
    {
        $candidates = array_merge($this->normalizeMobileCandidates($mobile), [trim((string) $mobile)]);
        foreach ($this->accounts as $id => $stored) {
            if ($id !== $customerId && in_array($stored, $candidates, true)) {
                return true;
            }
        }

        return false;
    }

    public function sendOtp($mobileNum)
    {
        $this->sendLog[] = (string) $mobileNum;
        $this->sent[(string) $mobileNum] = (string) $this->getOtp($mobileNum);
    }

    public function generateToken($customerId)
    {
        return 'token-' . $customerId;
    }

    public function changePasswordForCustomer($customerId, $password)
    {
        $this->passwords[(int) $customerId] = (string) $password;
    }
}

/**
 * Magento's account lock, recording what the service asks of it.
 */
class RecordingAuthentication implements AuthenticationInterface
{
    /** @var int[] */
    public array $failures = [];

    /** @var int[] */
    public array $lockChecks = [];

    /** @var int[] */
    public array $unlocked = [];

    public function processAuthenticationFailure($customerId)
    {
        $this->failures[] = (int) $customerId;
    }

    public function unlock($customerId)
    {
        $this->unlocked[] = (int) $customerId;
    }

    public function isLocked($customerId)
    {
        $this->lockChecks[] = (int) $customerId;

        return false;
    }

    public function authenticate($customerId, $password)
    {
        return true;
    }
}

/**
 * An anonymous caller.
 */
class GuestContext implements UserContextInterface
{
    public function getUserId()
    {
        return null;
    }

    public function getUserType()
    {
        return UserContextInterface::USER_TYPE_GUEST;
    }
}

/**
 * The client address the limits see.
 */
class FixedAddress extends RemoteAddress
{
    public function __construct(public string $address)
    {
    }

    public function getRemoteAddress(bool $ipToLong = false)
    {
        return $this->address;
    }
}
