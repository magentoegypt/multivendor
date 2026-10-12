<?php
declare(strict_types=1);
namespace MagentoEgypt\Fulfillment\Plugin;

final class Algolia
{
    public function __construct(private \MagentoEgypt\Fulfillment\Model\BrowseArea $area) {}
    public function afterIsInstantEnabled($subject,$result) { return $this->area->get() ? false : $result; }
    public function afterIsAutoCompleteEnabled($subject,$result) { return $this->area->get() ? false : $result; }
    public function afterIsEnabled($subject,$result) { return $this->area->get() ? false : $result; }
    public function afterGetCurrentSearchEngine($subject,$result) { return $this->area->get() ? 'opensearch' : $result; }
}
