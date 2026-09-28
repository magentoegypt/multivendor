<?php
declare(strict_types=1);

namespace MagentoEgypt\SetExtend\Logger;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * No log line can ever carry a runnable PHP open tag.
 *
 * IR 2026-09-28: the attacker put `<?=eval(...)?>` into requests Magento logs
 * verbatim ("GraphQL client error: Int cannot represent non-integer value: …",
 * "Requested store is not found (…)"), then made Magento `include`
 * var/log/system.log through a REST object-injection chain
 * (Setup\Module\Di\Code\Scanner\ArrayScanner::collectEntities). The logged
 * payload ran and wrote webshells into pub/. With "<?" defanged to "< ?" the
 * same include is inert text, whatever route reaches it next.
 *
 * Registered on Magento\Framework\Logger\Monolog (etc/di.xml), so every
 * handler of the main logger — system, debug, exception, syslog — and the
 * module loggers declared as its virtualTypes get it.
 */
class NeutralizePhpTags implements ProcessorInterface
{
    public function __invoke(LogRecord $record): LogRecord
    {
        return $record->with(
            message: self::clean($record->message),
            context: self::cleanArray($record->context),
            extra: self::cleanArray($record->extra)
        );
    }

    private static function clean(string $value): string
    {
        return str_contains($value, '<?') ? str_replace('<?', '< ?', $value) : $value;
    }

    private static function cleanArray(array $values, int $depth = 0): array
    {
        if ($depth > 8) {
            return $values;
        }

        foreach ($values as $key => $value) {
            if (is_string($value)) {
                $values[$key] = self::clean($value);
            } elseif (is_array($value)) {
                $values[$key] = self::cleanArray($value, $depth + 1);
            } elseif ($value instanceof \Throwable) {
                // Exception messages are logged verbatim too; keep the object
                // (formatters need its trace) but not an attacker-written message.
                if (str_contains($value->getMessage(), '<?')) {
                    $values[$key] = self::clean(get_class($value) . ': ' . $value->getMessage()
                        . ' in ' . $value->getFile() . ':' . $value->getLine());
                }
            }
        }

        return $values;
    }
}
