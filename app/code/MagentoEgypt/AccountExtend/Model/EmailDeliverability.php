<?php
/**
 * Can an email address's domain receive mail — and did the customer mean another?
 *
 * WHY ([CL036-TC97], 14zb93nw8hm)
 * -------------------------------
 * QA created an account as amira@magentoegypt.co. The format is valid (.co is a
 * real TLD), so format validation rightly let it through — but the domain has no
 * MX and no A record: nothing can ever be delivered there. Order confirmations,
 * password resets and invoices for that account would all vanish.
 *
 * WHAT IT CHECKS
 * --------------
 * Only the DOMAIN, by DNS: an MX record, or failing that an A/AAAA record (the
 * implicit MX of RFC 5321 §5.1). A "null MX" (RFC 7505, target ".") means the
 * domain declares it accepts no mail. The MAILBOX is not checked: that needs an
 * SMTP conversation on port 25, which is blocked from this host, and Gmail,
 * Outlook and catch-all domains do not reveal mailboxes anyway.
 *
 * FAILS OPEN. A DNS error or timeout is not evidence the domain is dead, so it
 * answers "deliverable" (null = unknown) rather than turn a customer away.
 * Results are cached: a day for a deliverable domain, an hour for a dead one.
 *
 * SUGGESTIONS ("Did you mean …?")
 * -------------------------------
 * Two rules, and a suggestion is only made if the suggested domain itself
 * receives mail:
 *   - a mistyped ".com" (".co", ".cm", ".con", ".om", …) → the ".com" domain:
 *     magentoegypt.co → magentoegypt.com;
 *   - within two typing errors of a common mail provider:
 *     gmial.com → gmail.com, hotmial.com → hotmail.com.
 */
declare(strict_types=1);

namespace MagentoEgypt\AccountExtend\Model;

use Magento\Framework\App\CacheInterface;
use Psr\Log\LoggerInterface;

class EmailDeliverability
{
    private const CACHE_PREFIX = 'hm_email_domain_';
    private const TTL_DELIVERABLE = 86400;
    private const TTL_DEAD = 3600;

    /** Common mailbox providers a near-miss is compared against. */
    private const PROVIDERS = [
        'gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'icloud.com', 'live.com',
        'msn.com', 'aol.com', 'yandex.com', 'protonmail.com', 'proton.me', 'mail.com',
        'gmx.com', 'me.com', 'hotmail.co.uk', 'yahoo.co.uk', 'outlook.sa', 'yahoo.com.eg',
    ];

    /** TLDs that are almost always a mistyped ".com". */
    private const COM_TYPOS = ['co', 'cm', 'con', 'om', 'comm', 'cpm', 'xom', 'vom', 'cim', 'coom', 'cmo', 'ocm', 'c'];

    /** @var array<string, bool|null> per-request memo */
    private array $memo = [];

    public function __construct(
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @return array{domain: string, deliverable: bool|null, suggestion: string|null}
     *         deliverable null = could not be determined (treat as deliverable)
     */
    public function check(string $email): array
    {
        $email = trim($email);
        $at = strrpos($email, '@');
        if ($at === false || $at === 0 || $at === strlen($email) - 1) {
            return ['domain' => '', 'deliverable' => null, 'suggestion' => null];
        }

        $local = substr($email, 0, $at);
        $domain = strtolower(rtrim(substr($email, $at + 1), '.'));
        $deliverable = $this->domainReceivesMail($domain);
        $suggestedDomain = $this->suggestDomain($domain, $deliverable);

        return [
            'domain' => $domain,
            'deliverable' => $deliverable,
            'suggestion' => $suggestedDomain !== null ? $local . '@' . $suggestedDomain : null,
        ];
    }

    /**
     * True when the address is known to be undeliverable (fail open on unknown).
     */
    public function isUndeliverable(string $email): bool
    {
        return $this->check($email)['deliverable'] === false;
    }

    /**
     * @return bool|null null when DNS could not answer
     */
    public function domainReceivesMail(string $domain): ?bool
    {
        if ($domain === '' || !str_contains($domain, '.')) {
            return false;
        }
        if (array_key_exists($domain, $this->memo)) {
            return $this->memo[$domain];
        }

        $key = self::CACHE_PREFIX . md5($domain);
        $cached = $this->cache->load($key);
        if ($cached === '1' || $cached === '0') {
            return $this->memo[$domain] = ($cached === '1');
        }

        $result = $this->lookup($domain);
        if ($result !== null) {
            $this->cache->save($result ? '1' : '0', $key, [], $result ? self::TTL_DELIVERABLE : self::TTL_DEAD);
        }

        return $this->memo[$domain] = $result;
    }

    private function lookup(string $domain): ?bool
    {
        $ascii = function_exists('idn_to_ascii')
            ? (idn_to_ascii($domain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46) ?: $domain)
            : $domain;

        $mx = $this->dns($ascii, DNS_MX);
        if ($mx === null) {
            return null;
        }
        if ($mx) {
            foreach ($mx as $record) {
                $target = rtrim((string) ($record['target'] ?? ''), '.');
                if ($target !== '') {
                    return true;
                }
            }

            return false; // only a null MX: the domain says it takes no mail
        }

        $a = $this->dns($ascii, DNS_A);
        if ($a === null) {
            return null;
        }
        if ($a) {
            return true;
        }

        $aaaa = $this->dns($ascii, DNS_AAAA);

        return $aaaa === null ? null : (bool) $aaaa;
    }

    /**
     * @return array<int, array<string, mixed>>|null null on resolver error
     */
    private function dns(string $domain, int $type): ?array
    {
        $failed = false;
        set_error_handler(static function () use (&$failed) {
            $failed = true;

            return true;
        });
        try {
            $records = dns_get_record($domain, $type);
        } catch (\Throwable $e) {
            $records = false;
        } finally {
            restore_error_handler();
        }

        if ($records === false || ($failed && !$records)) {
            $this->logger->info('EmailDeliverability: DNS lookup failed for ' . $domain);

            return null;
        }

        return $records;
    }

    private function suggestDomain(string $domain, ?bool $deliverable): ?string
    {
        if ($domain === '' || in_array($domain, self::PROVIDERS, true)) {
            return null;
        }

        $candidates = [];

        $dot = strrpos($domain, '.');
        if ($dot !== false && $deliverable === false) {
            $tld = substr($domain, $dot + 1);
            if (in_array($tld, self::COM_TYPOS, true)) {
                $candidates[] = substr($domain, 0, $dot) . '.com';
            }
        }

        $best = null;
        $bestDistance = 3;
        foreach (self::PROVIDERS as $provider) {
            $distance = levenshtein($domain, $provider);
            if ($distance > 0 && $distance < $bestDistance) {
                $best = $provider;
                $bestDistance = $distance;
            }
        }
        if ($best !== null) {
            $candidates[] = $best;
        }

        foreach ($candidates as $candidate) {
            if ($candidate !== $domain && $this->domainReceivesMail($candidate) !== false) {
                return $candidate;
            }
        }

        return null;
    }
}
