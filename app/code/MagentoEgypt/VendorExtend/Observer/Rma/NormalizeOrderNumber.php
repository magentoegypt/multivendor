<?php
declare(strict_types=1);

namespace MagentoEgypt\VendorExtend\Observer\Rma;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Admin Returns > New Request: accept the order number the way admin prints it ("#000000097").
 *
 * The Order # field is looked up with an exact increment_id match: vrma/request/loadItem does
 * Order::load($value, 'increment_id') and vrma/request/save does loadByIncrementId(). Admin shows
 * every order as "#000000097" (order view title, grids, emails), so a number copied from there
 * matched nothing. The form then said "This order is not exist, you're only allowed for chosing the
 * order, which has the status is complete or processing" about a Complete order, and emptied the
 * field (ClickUp TC39-QA02 14zb93nuzv7, 2026-09-26).
 *
 * Runs before either action and drops surrounding spaces and a leading "#". The number itself is
 * never altered otherwise.
 * No constructor dependencies on purpose (added without a di:compile).
 */
class NormalizeOrderNumber implements ObserverInterface
{
    private const ACTIONS = ['vrma_request_loaditem', 'vrma_request_save'];

    public function execute(Observer $observer)
    {
        /** @var \Magento\Framework\App\Request\Http $request */
        $request = $observer->getEvent()->getData('request');
        if (!$request || !in_array(strtolower((string) $request->getFullActionName()), self::ACTIONS, true)) {
            return;
        }

        $lookup = $request->getParam('increment_id');
        if (is_string($lookup)) {
            $request->setParam('increment_id', $this->normalize($lookup));
        }

        $posted = $request->getPostValue('order_incremental_id');
        if (is_string($posted)) {
            $request->setPostValue('order_incremental_id', $this->normalize($posted));
        }
    }

    private function normalize(string $number): string
    {
        return trim(ltrim(trim($number), '#'));
    }
}
