<?php
declare(strict_types=1);
namespace MagentoEgypt\Fulfillment\Model;

final class QuotePlan
{
    public function __construct(private Preview $preview, private \MagentoEgypt\DeliveryAvailability\Model\Availability $availability) {}

    public function items(array $items): array
    {
        $lines=[];
        foreach ($items as $item) {
            if ($item->getProduct()->isVirtual() || $item->getProductType()==='configurable') continue;
            $qty=(float)$item->getQty();
            if ($item->getParentItem()) $qty *= (float)$item->getParentItem()->getQty();
            $milli=(int)round($qty*1000);
            if (abs($milli/1000-$qty)>0.000001) throw new \DomainException('Quantity precision exceeds three decimal places.');
            $lines[]=['sku'=>(string)$item->getProduct()->getSku(),'qty_milli'=>$milli];
        }
        return $lines;
    }

    public function forAddress(array $items, $address, string $strategy): array
    {
        $location=$this->availability->location((string)$address->getCountryId(),(int)$address->getRegionId(),
            (int)$address->getData('cm_city_id'),(int)$address->getData('cm_locality_id'),(string)$address->getCity());
        return $this->preview->plan($this->items($items),(string)$location['country_id'],(int)$location['region_id'],
            (int)$location['location_id'],(int)($location['locality']['location_id']??0),$strategy);
    }
}
