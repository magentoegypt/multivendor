<?php
declare(strict_types=1);
namespace MagentoEgypt\Fulfillment\Plugin;

/** Frontend only. URL parameters separate full-page cache entries for each selected area. */
final class CatalogCollection
{
    public function __construct(private \MagentoEgypt\Fulfillment\Model\BrowseArea $area,
        private \MagentoEgypt\Fulfillment\Model\CatalogEligibility $eligibility) {}
    private function apply($collection): void
    {
        if ($collection->getFlag('hf_area_applied')) return;
        $area=$this->area->get(); if (!$area) return;
        $collection->setFlag('hf_area_applied',true);
        $collection->addIdFilter($this->eligibility->ids(...$area) ?: [0]);
    }
    public function beforeLoad($subject, $printQuery=false, $logQuery=false): array
    { $this->apply($subject); return [$printQuery,$logQuery]; }
    public function beforeGetSize($subject): void { $this->apply($subject); }
}
