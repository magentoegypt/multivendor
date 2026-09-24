<?php
declare(strict_types=1);

namespace MagentoEgypt\AlgoliaVendor\Model;

/**
 * Temporarily answers "which search engine?" with a fixed value.
 *
 * Magento's OpenSearch stack (client, field mappers, data providers, index
 * switcher, collection factories) is chosen at call time from
 * EngineResolver::getCurrentSearchEngine(). While catalog/search/engine is
 * `algolia`, running an OpenSearch call therefore needs the resolver to say
 * `opensearch` for the duration of that call — and only that call.
 * See Plugin\EngineResolverPlugin, which reads this.
 */
final class EngineOverride
{
    private static ?string $engine = null;

    public static function current(): ?string
    {
        return self::$engine;
    }

    /**
     * @template T
     * @param callable():T $fn
     * @return T
     */
    public static function run(string $engine, callable $fn): mixed
    {
        $previous = self::$engine;
        self::$engine = $engine;

        try {
            return $fn();
        } finally {
            self::$engine = $previous;
        }
    }
}
