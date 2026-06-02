<?php

declare(strict_types=1);

namespace MagentoEgypt\OdooConnector\Model\Sync;

use Magento\Framework\Serialize\Serializer\Json;

/**
 * Canonical SHA-256 over a payload: keys are sorted recursively so that
 * equal data always produces the same hash. Used for echo-suppression and
 * conflict detection (see architecture doc section 2).
 */
class Checksum
{
    private Json $json;

    public function __construct(Json $json)
    {
        $this->json = $json;
    }

    public function hash(array $payload): string
    {
        return hash('sha256', $this->json->serialize($this->canonicalize($payload)));
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private function canonicalize($value)
    {
        if (!is_array($value)) {
            return $value;
        }

        $isList = array_keys($value) === range(0, count($value) - 1);
        $out = [];
        foreach ($value as $key => $item) {
            $out[$key] = $this->canonicalize($item);
        }
        if (!$isList) {
            ksort($out);
        }

        return $out;
    }
}
