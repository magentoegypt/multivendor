<?php
declare(strict_types=1);

namespace MagentoEgypt\VendorExtend\Observer;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Vnecoms\VendorsProduct\Model\Product\Update;
use Vnecoms\VendorsProduct\Model\Source\Approval;

/**
 * Hub Market: Catalog > Products cannot silently swallow a seller's pending update (ClickUp TC68-QA03 14zb93nvqy5).
 *
 * When a seller edits name, price, etc. of an approved product, Vnecoms does not change the product: it queues the
 * new values in `ves_vendor_product_update` (status pending) and sets `approval` = Pending Update. Only Vnecoms'
 * Approve action (Marketplace > Manage Pending Products) copies the queued values onto the product. QA kept
 * "approving" from Catalog > Products instead: set Approval = Approved, Save. That saved the OLD values the form
 * shows, flipped the product to Approved and left the queue pending for ever, so the seller's edits "reverted"
 * (three rounds; products 2401 and 2413 on 2026-09-30).
 *
 *   controller_action_predispatch_catalog_product_edit
 *       a warning at the top of the edit page that lists what the seller asked for, and how to accept or refuse it;
 *   controller_action_catalog_product_save_entity_after
 *       saving the product as Approved applies the queued values, exactly as Vnecoms' Approve does (then the same
 *       seller notification + approval email, and ProductApprovalGoLive reindexes and purges). A field the admin
 *       changed in this same save keeps the admin's value.
 *
 * No constructor dependencies on purpose: added without a di:compile (production's compiled DI builds unknown
 * classes with no arguments). Failures are logged and shown, never thrown: the admin's own save already happened.
 */
class PendingProductUpdate implements ObserverInterface
{
    /** Longest value quoted in the warning (descriptions). */
    private const MAX_VALUE_LENGTH = 80;

    public function execute(Observer $observer)
    {
        $event = $observer->getEvent();
        if ($event->getName() === 'controller_action_catalog_product_save_entity_after') {
            $product = $event->getData('product');
            if ($product instanceof Product && $product->getId()) {
                $this->applyOnApprovedSave($product);
            }
            return;
        }

        $productId = (int) $event->getData('request')?->getParam('id');
        if ($productId) {
            $this->warn($productId);
        }
    }

    /**
     * Edit page: say that the form shows the current values, and what is waiting.
     */
    private function warn(int $productId): void
    {
        $om = ObjectManager::getInstance();
        try {
            $updates = $this->pendingUpdates($productId);
            if (!count($updates)) {
                return;
            }
            $product = $om->create(Product::class)->setStoreId(0)->load($productId);
            $lines = [];
            $sent = '';
            foreach ($updates as $update) {
                $sent = (string) $update->getData('created_at');
                foreach ($this->unserialize((string) $update->getData('product_data')) as $code => $value) {
                    if ($value === null) {
                        continue;
                    }
                    $lines[] = $this->label($code) . ': ' . $this->display($product, $code, $value);
                }
            }
            if (!$lines) {
                return;
            }
            $om->get(\Magento\Framework\Message\ManagerInterface::class)->addWarningMessage(
                __(
                    'The seller changed this product and the change is waiting for approval (%1): %2. '
                    . 'The fields below still show the current values. To accept the change, set Approval to '
                    . '"Approved" and Save, or use Marketplace > Manage Pending Products > Approve. '
                    . 'To refuse it, use Reject there.',
                    $sent,
                    implode('; ', $lines)
                )
            );
        } catch (\Throwable $e) {
            $om->get(\Psr\Log\LoggerInterface::class)
                ->error('Hub Market pending-update warning failed for product ' . $productId . ': ' . $e->getMessage());
        }
    }

    /**
     * Save: an Approved product takes the seller's queued values, as Vnecoms' Approve controller would.
     */
    private function applyOnApprovedSave(Product $product): void
    {
        if ((int) $product->getData('approval') !== Approval::STATUS_APPROVED) {
            return;
        }
        $om = ObjectManager::getInstance();
        $messages = $om->get(\Magento\Framework\Message\ManagerInterface::class);
        $productId = (int) $product->getId();
        try {
            $updates = $this->pendingUpdates($productId);
            if (!count($updates)) {
                return;
            }
            $updateIds = [];
            $applied = [];
            $kept = [];
            foreach ($updates as $update) {
                $storeId = (int) $update->getData('store_id');
                /** @var Product $target */
                $target = $om->create(Product::class)->setStoreId($storeId)->load($productId);
                $hasCategories = false;
                $changed = false;
                foreach ($this->unserialize((string) $update->getData('product_data')) as $code => $value) {
                    if ($value === null) {
                        continue;
                    }
                    if ($storeId === (int) $product->getStoreId() && $this->adminChanged($product, $code)) {
                        $kept[$code] = $this->label($code);
                        continue;
                    }
                    $target->setData($code, $value);
                    $applied[$code] = $this->label($code);
                    $changed = true;
                    $hasCategories = $hasCategories || $code === 'category_ids';
                }
                if ($changed) {
                    $target->save();
                    if ($hasCategories) {
                        $om->get(\Magento\Catalog\Api\CategoryLinkManagementInterface::class)
                            ->assignProductToCategories($target->getSku(), $target->getCategoryIds());
                    }
                }
                $update->setStatus(Update::STATUS_APPROVED)->setId($update->getData('update_id'))->save();
                $updateIds[] = (int) $update->getData('update_id');
            }

            $vendorId = (int) $product->getData('vendor_id');
            $om->get(\Magento\Framework\Event\ManagerInterface::class)->dispatch(
                'vnecoms_vendors_push_notification',
                [
                    'vendor_id' => $vendorId,
                    'type' => 'product_approval',
                    'message' => __('Updates of %1 are approved', '<strong>' . $product->getName() . '</strong>'),
                    'additional_info' => ['id' => $productId],
                ]
            );
            $om->create(\Vnecoms\VendorsProduct\Model\Queue::class)
                ->publish('update_approval_product', $updateIds, $vendorId, $productId);

            if ($applied) {
                $messages->addSuccessMessage(
                    __("Applied the seller's pending changes: %1.", implode(', ', $applied))
                );
            }
            if ($kept) {
                $messages->addNoticeMessage(
                    __('Kept your own value instead of the seller\'s for: %1.', implode(', ', $kept))
                );
            }
        } catch (\Throwable $e) {
            $om->get(\Psr\Log\LoggerInterface::class)
                ->error('Hub Market apply-on-save failed for product ' . $productId . ': ' . $e->getMessage());
            $messages->addErrorMessage(
                __(
                    "The product was saved, but the seller's pending changes were not applied: %1. "
                    . 'Use Marketplace > Manage Pending Products > Approve.',
                    $e->getMessage()
                )
            );
        }
    }

    private function pendingUpdates(int $productId): \Vnecoms\VendorsProduct\Model\ResourceModel\Product\Update\Collection
    {
        return ObjectManager::getInstance()
            ->create(\Vnecoms\VendorsProduct\Model\ResourceModel\Product\Update\Collection::class)
            ->addFieldToFilter('product_id', $productId)
            ->addFieldToFilter('status', Update::STATUS_PENDING)
            ->setOrder('update_id', 'ASC');
    }

    private function unserialize(string $data): array
    {
        if ($data === '') {
            return [];
        }
        $value = ObjectManager::getInstance()->get(\Magento\Framework\Serialize\Serializer\Serialize::class)
            ->unserialize($data);
        return is_array($value) ? $value : [];
    }

    /**
     * Did the admin type a different value for $code in this save? (The form posts every field.)
     */
    private function adminChanged(Product $product, string $code): bool
    {
        $now = $product->getData($code);
        $was = $product->getOrigData($code);
        if (is_array($now) || is_array($was)) {
            $now = array_map('strval', (array) $now);
            $was = array_map('strval', (array) $was);
            sort($now);
            sort($was);
            return $now !== $was;
        }
        if (is_numeric($now) && is_numeric($was)) {
            return abs((float) $now - (float) $was) > 0.00001;
        }
        return trim((string) $now) !== trim((string) $was);
    }

    private function label(string $code): string
    {
        try {
            $attribute = ObjectManager::getInstance()
                ->get(\Magento\Catalog\Api\ProductAttributeRepositoryInterface::class)->get($code);
            return (string) ($attribute->getDefaultFrontendLabel() ?: $code);
        } catch (\Throwable $e) {
            return $code;
        }
    }

    /**
     * `"new value" (now "current value")`, as an admin reads them: option labels, category names, plain short text.
     */
    private function display(Product $product, string $code, $value): string
    {
        $new = $this->format($code, $value);
        $current = $this->format($code, $product->getData($code));
        return '"' . $new . '"' . ($current !== '' && $current !== $new ? ' (now "' . $current . '")' : '');
    }

    private function format(string $code, $value): string
    {
        if ($value === null || $value === '' || $value === []) {
            return '';
        }
        $om = ObjectManager::getInstance();
        if ($code === 'category_ids') {
            $value = $om->create(\Magento\Catalog\Model\ResourceModel\Category\Collection::class)
                ->addAttributeToSelect('name')
                ->addAttributeToFilter('entity_id', ['in' => (array) $value])
                ->getColumnValues('name');
        } else {
            try {
                $attribute = $om->get(\Magento\Catalog\Api\ProductAttributeRepositoryInterface::class)->get($code);
                if ($attribute->usesSource()) {
                    $texts = [];
                    foreach (is_array($value) ? $value : explode(',', (string) $value) as $optionId) {
                        $text = $attribute->getSource()->getOptionText($optionId);
                        $texts[] = is_array($text) ? implode(', ', $text) : (string) ($text ?: $optionId);
                    }
                    $value = $texts;
                }
            } catch (\Throwable $e) {
                // not a product attribute: show the raw value
            }
        }
        if (is_array($value)) {
            $value = implode(', ', array_map(
                static fn ($v) => is_scalar($v) ? (string) $v : (string) json_encode($v),
                $value
            ));
        } elseif (is_numeric($value)) {
            $value = (string) (0 + $value);
        }
        $text = trim((string) preg_replace('/\s+/u', ' ', strip_tags((string) $value)));
        if (mb_strlen($text) > self::MAX_VALUE_LENGTH) {
            $text = mb_substr($text, 0, self::MAX_VALUE_LENGTH - 1) . '…';
        }
        return $text;
    }
}
