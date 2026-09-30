<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppAccount\Test\Unit\Model\Otp;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthenticationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use MagentoEgypt\HubAppAccount\Model\Otp\DeliveryNumber;
use MagentoEgypt\HubAppAccount\Model\Otp\OtpSendStatus;
use MagentoEgypt\HubAppAccount\Model\Otp\WhatsAppSignIn;
use MagentoEgypt\SmsExtend\Api\WhatsAppInterface;
use MagentoEgypt\SmsExtend\Helper\Otp;
use MagentoEgypt\SmsExtend\Model\Otp\OtpGuard;
use MagentoEgypt\SmsExtend\Model\Throttle;
use MagentoEgypt\SmsExtend\Model\WhatsAppManagement;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Vnecoms\Sms\Helper\Data as SmsHelper;

/**
 * hmSendWhatsAppCode / hmSignInWithWhatsAppCode with fakes for the OTP helper, the WhatsApp service,
 * the SMS settings and the config, and SmsExtend's real OtpGuard (the limits the REST service shares).
 * Nothing is ever sent.
 */
class WhatsAppSignInTest extends TestCase
{
    private const UNIFORM = 'If this number belongs to an account, we have sent a sign-in code to it on WhatsApp.';

    /** The one answer while hubapp/otp/reveal_unknown_number is off. */
    private const MASKED_ANSWER = [
        'sent' => true,
        'status' => OtpSendStatus::MASKED,
        'message' => self::UNIFORM,
        'resend_after_seconds' => 45,
    ];

    private FakeOtp $otp;

    private FakeWhatsApp $whatsApp;

    /** @var array<string, mixed> */
    private array $config;

    protected function setUp(): void
    {
        $this->otp = new FakeOtp();
        $this->whatsApp = new FakeWhatsApp();
        $this->config = [
            WhatsAppSignIn::XML_REVEAL_UNKNOWN => '0',
            OtpGuard::XML_SEND_LIMIT_IP => '10',
            OtpGuard::XML_SEND_LIMIT_NUMBER => '5',
        ];
    }

    private function signIn(): WhatsAppSignIn
    {
        $config = $this->config;
        $scopeConfig = new class ($config) implements ScopeConfigInterface {
            /** @param array<string, mixed> $values */
            public function __construct(private readonly array $values)
            {
            }

            public function getValue($path, $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeCode = null)
            {
                return $this->values[$path] ?? null;
            }

            public function isSetFlag($path, $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeCode = null)
            {
                return !empty($this->values[$path]);
            }
        };
        $sms = new class () extends SmsHelper {
            public function __construct()
            {
            }

            public function getOtpResendPeriodTime($storeId = null)
            {
                return 45;
            }
        };

        $cache = new MemoryCache();

        return new WhatsAppSignIn(
            $this->otp,
            new DeliveryNumber($this->otp),
            new OtpGuard(new Throttle($cache), $cache, $scopeConfig),
            $this->whatsApp,
            $sms,
            $scopeConfig,
            new NullLogger()
        );
    }

    public function testKnownNumberGetsTheCodeOnItsStoredNumberWithTheUniformAnswer(): void
    {
        $this->otp->accounts = ['971501234567' => [31 => '+971501234567']];

        $answer = $this->signIn()->sendCode('971501234567', '10.0.0.1');

        self::assertSame(['+971501234567'], $this->otp->sent);
        self::assertSame(self::MASKED_ANSWER, $answer);
    }

    public function testUnknownNumberGetsTheSameAnswerAndNothingIsSent(): void
    {
        $answer = $this->signIn()->sendCode('+971509999999', '10.0.0.1');

        self::assertSame([], $this->otp->sent);
        self::assertSame(self::MASKED_ANSWER, $answer);
    }

    public function testTypedSpellingNeverReceivesTheCode(): void
    {
        //  The Belize case: an account found for the typed "+501234567" (the fake lookup forces the match
        //  the real one no longer makes) stores the bare UAE mobile "501234567". The code goes to that
        //  account's own number, +971501234567, never to the typed +501 (Belize) spelling.
        $this->otp->accounts = ['+501234567' => [8 => '501234567']];

        $answer = $this->signIn()->sendCode('+501234567', '10.0.0.1');

        self::assertSame(['+971501234567'], $this->otp->sent);
        self::assertSame(self::UNIFORM, $answer['message']);
        self::assertTrue($answer['sent']);
    }

    public function testAmbiguousNumberSendsNothing(): void
    {
        $this->otp->accounts = ['+971501234567' => [1 => '+971501234567', 2 => '971501234567']];

        self::assertTrue($this->signIn()->sendCode('+971501234567', '10.0.0.1')['sent']);
        self::assertSame([], $this->otp->sent);
    }

    public function testResendCooldownAnswersLikeASend(): void
    {
        $this->otp->accounts = ['+971501234567' => [31 => '+971501234567']];
        $this->otp->cooldown = true;

        self::assertSame(self::MASKED_ANSWER, $this->signIn()->sendCode('+971501234567', '10.0.0.1'));
    }

    public function testWithoutTheRevealSettingNoStatusTellsWhetherTheNumberHasAnAccount(): void
    {
        //  Known, unknown, shared, undeliverable, inside the cooldown, a gateway error: one answer.
        $this->otp->accounts = [
            '+971501234567' => [31 => '+971501234567'],
            '+971502222222' => [1 => '+971502222222', 2 => '971502222222'],
            '+447911123456' => [8 => '447911123456'],
            '+971503333333' => [40 => '+971503333333'],
        ];
        $signIn = $this->signIn();
        $answers = [
            $signIn->sendCode('+971501234567', '10.0.0.1'),
            $signIn->sendCode('+971509999999', '10.0.0.2'),
            $signIn->sendCode('+971502222222', '10.0.0.3'),
            $signIn->sendCode('+447911123456', '10.0.0.4'),
        ];
        $this->otp->cooldown = true;
        $answers[] = $signIn->sendCode('+971501234567', '10.0.0.5');
        $this->otp->cooldown = false;
        $this->otp->failure = true;
        $answers[] = $signIn->sendCode('+971503333333', '10.0.0.6');

        foreach ($answers as $answer) {
            self::assertSame(self::MASKED_ANSWER, $answer);
        }
        self::assertSame(OtpSendStatus::WITHOUT_REVEAL, [OtpSendStatus::THROTTLED, OtpSendStatus::MASKED]);
    }

    public function testRevealModeNamesTheReason(): void
    {
        $this->config[WhatsAppSignIn::XML_REVEAL_UNKNOWN] = '1';
        $this->otp->accounts = [
            '+971501234567' => [31 => '+971501234567'],
            '+447911123456' => [8 => '447911123456'],
            '+971502222222' => [1 => '+971502222222', 2 => '971502222222'],
        ];
        $signIn = $this->signIn();

        $known = $signIn->sendCode('+971501234567', '10.0.0.1');
        self::assertTrue($known['sent']);
        self::assertSame(OtpSendStatus::SENT, $known['status']);
        self::assertSame('We have sent a sign-in code to your WhatsApp.', $known['message']);

        $unknown = $signIn->sendCode('+971509999999', '10.0.0.2');
        self::assertFalse($unknown['sent']);
        self::assertSame(OtpSendStatus::NO_ACCOUNT, $unknown['status']);
        self::assertSame('No account uses this mobile number.', $unknown['message']);

        //  A bare foreign number has no canonical form: nothing is sent, never guessed.
        $undeliverable = $signIn->sendCode('+447911123456', '10.0.0.3');
        self::assertFalse($undeliverable['sent']);
        self::assertSame(OtpSendStatus::UNDELIVERABLE, $undeliverable['status']);
        self::assertStringContainsString('sign in with your email address', $undeliverable['message']);

        $shared = $signIn->sendCode('+971502222222', '10.0.0.4');
        self::assertFalse($shared['sent']);
        self::assertSame(OtpSendStatus::MULTIPLE, $shared['status']);
        self::assertStringContainsString('more than one account', $shared['message']);

        self::assertSame(['+971501234567'], $this->otp->sent);
    }

    public function testRevealModeTellsTheCooldownAndAFailedSendApart(): void
    {
        $this->config[WhatsAppSignIn::XML_REVEAL_UNKNOWN] = '1';
        $this->otp->accounts = ['+971501234567' => [31 => '+971501234567']];
        $this->otp->cooldown = true;

        //  The code sent within the resend period still works: sent, but no new one.
        $cooling = $this->signIn()->sendCode('+971501234567', '10.0.0.1');
        self::assertSame(
            [
                'sent' => true,
                'status' => OtpSendStatus::COOLDOWN,
                'message' => 'We have already sent a sign-in code to your WhatsApp. Use that code, or ask for another in a moment.',
                'resend_after_seconds' => 45,
            ],
            $cooling
        );
        self::assertSame([], $this->otp->sent);

        $this->otp->cooldown = false;
        $this->otp->failure = true;
        $failed = $this->signIn()->sendCode('+971501234567', '10.0.0.2');
        self::assertFalse($failed['sent']);
        self::assertSame(OtpSendStatus::FAILED, $failed['status']);
        self::assertSame('We couldn\'t send the code. Please try again in a few minutes.', $failed['message']);
    }

    public function testPerNumberLimitCountsEverySpellingAndUnknownNumbersToo(): void
    {
        $this->config[OtpGuard::XML_SEND_LIMIT_NUMBER] = '2';
        $signIn = $this->signIn();

        self::assertTrue($signIn->sendCode('01001234567', '10.0.0.1')['sent']);
        self::assertTrue($signIn->sendCode('+20 100 123 4567', '10.0.0.2')['sent']);
        $limited = $signIn->sendCode('201001234567', '10.0.0.3');

        self::assertFalse($limited['sent']);
        self::assertSame(OtpSendStatus::THROTTLED, $limited['status']);
        self::assertGreaterThan(0, $limited['resend_after_seconds']);
    }

    public function testALimitIsThrottledWhateverTheRevealSettingAndTheNumber(): void
    {
        foreach (['0', '1'] as $reveal) {
            $this->config[WhatsAppSignIn::XML_REVEAL_UNKNOWN] = $reveal;
            $this->config[OtpGuard::XML_SEND_LIMIT_NUMBER] = '1';
            $this->otp->accounts = ['+971501234567' => [31 => '+971501234567']];
            //  A fresh guard (fresh counters) per setting.
            $signIn = $this->signIn();
            $signIn->sendCode('+971501234567', '10.0.0.1');
            $signIn->sendCode('+971509999999', '10.0.0.1');

            foreach (['+971501234567', '+971509999999'] as $number) {
                $limited = $signIn->sendCode($number, '10.0.0.2');
                self::assertFalse($limited['sent']);
                self::assertSame(OtpSendStatus::THROTTLED, $limited['status']);
                self::assertMatchesRegularExpression(
                    '/^Too many code requests\. Please try again in \d+ minutes\.$/',
                    $limited['message']
                );
                self::assertGreaterThan(0, $limited['resend_after_seconds']);
            }
        }
    }

    public function testPerAddressLimit(): void
    {
        $this->config[OtpGuard::XML_SEND_LIMIT_IP] = '1';
        $signIn = $this->signIn();

        self::assertTrue($signIn->sendCode('+971501111111', '10.0.0.9')['sent']);
        $limited = $signIn->sendCode('+971502222222', '10.0.0.9');
        self::assertFalse($limited['sent']);
        self::assertSame(OtpSendStatus::THROTTLED, $limited['status']);
        self::assertTrue($signIn->sendCode('+971502222222', '10.0.0.10')['sent']);
    }

    public function testNotAPhoneNumberIsAnInputError(): void
    {
        $this->expectException(GraphQlInputException::class);
        $this->signIn()->sendCode('call me', '10.0.0.1');
    }

    public function testSignInReturnsTheToken(): void
    {
        $this->whatsApp->result = new DataObject(['status' => 'success', 'token' => 'jwt-token']);

        self::assertSame('jwt-token', $this->signIn()->signIn(' +971501234567 ', ' 123456 '));
        self::assertSame([['+971501234567', '123456', 'LOGIN']], $this->whatsApp->verified);
    }

    public function testEveryFailureIsTheSameAuthenticationError(): void
    {
        foreach (['Mobile number not found.', 'Invalid OTP.', 'OTP has expired or does not exist.'] as $reason) {
            $this->whatsApp->result = new DataObject(['status' => 'error', 'message' => $reason, 'token' => '']);
            try {
                $this->signIn()->signIn('+971501234567', '123456');
                self::fail('no exception for: ' . $reason);
            } catch (GraphQlAuthenticationException $e) {
                self::assertSame('That code is incorrect or has expired. Check it, or ask for a new code.', $e->getMessage());
            }
        }
    }

    public function testALockedNumberSaysWhenToTryAgain(): void
    {
        //  WhatsAppManagement::verifyOtp answers a lock alike for every number, with or without an account.
        $this->whatsApp->result = new DataObject([
            'status' => 'error',
            'message' => 'Too many incorrect codes. Please try again in 15 minutes.',
            'token' => '',
            WhatsAppManagement::RETRY_AFTER => 890,
        ]);

        try {
            $this->signIn()->signIn('+971501234567', '123456');
            self::fail('no exception');
        } catch (GraphQlAuthenticationException $e) {
            self::assertSame('Too many incorrect codes. Please try again in 15 minutes.', $e->getMessage());
        }
    }

    public function testMalformedCodeIsRefusedBeforeItCountsAgainstTheAccount(): void
    {
        try {
            $this->signIn()->signIn('+971501234567', '12345');
            self::fail('no exception');
        } catch (GraphQlInputException $e) {
            self::assertSame([], $this->whatsApp->verified);
        }
    }
}

/**
 * The OTP helper with the real canonicaliser and a fake account lookup and sender.
 */
class FakeOtp extends Otp
{
    /** @var array<string, array<int, string>> typed number => [customer id => stored number] */
    public array $accounts = [];

    /** @var string[] numbers a code was "sent" to */
    public array $sent = [];

    public bool $cooldown = false;

    /** The send breaks for another reason than the cooldown. */
    public bool $failure = false;

    public function __construct()
    {
    }

    public function getCustomersByMobile($input)
    {
        $out = [];
        foreach ($this->accounts[$input] ?? [] as $id => $stored) {
            $out[] = new DataObject(['id' => $id, 'mobilenumber' => $stored]);
        }

        return $out;
    }

    public function sendOtp($mobileNum)
    {
        if ($this->cooldown) {
            throw new LocalizedException(__('Please wait %1 seconds before requesting another code.', 30));
        }
        if ($this->failure) {
            throw new \RuntimeException('WhatsApp gateway unreachable');
        }
        $this->sent[] = $mobileNum;
    }
}

/**
 * WhatsAppManagement stand-in: records verify calls, answers with a preset result.
 */
class FakeWhatsApp implements WhatsAppInterface
{
    public ?DataObject $result = null;

    /** @var array<int, array{string, string, string}> */
    public array $verified = [];

    public function sendOtp($mobile, $type)
    {
        throw new \LogicException('the app never sends through the REST service');
    }

    public function verifyOtp($mobile, $otp, $type, $password = '')
    {
        $this->verified[] = [$mobile, $otp, $type];

        return $this->result ?? new DataObject(['status' => 'error', 'message' => 'Invalid OTP.', 'token' => '']);
    }
}
