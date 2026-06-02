<?php

declare(strict_types=1);

namespace MagentoEgypt\OdooConnector\Model\Product;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use MagentoEgypt\OdooConnector\Model\Api\OdooClient;

/**
 * Maps a Magento product's categories to an Odoo product.category id, creating
 * the Odoo category by name when it does not yet exist. Returns the primary
 * (first non-root) category — Odoo product.template has a single categ_id.
 */
class CategoryResolver
{
    private OdooClient $odooClient;
    private CategoryRepositoryInterface $categoryRepository;

    /** @var array<string, int> in-request name->odoo_id cache */
    private array $cache = [];

    public function __construct(OdooClient $odooClient, CategoryRepositoryInterface $categoryRepository)
    {
        $this->odooClient = $odooClient;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * @param int[]|string[] $categoryIds
     */
    public function resolvePrimaryCategId(array $categoryIds): ?int
    {
        foreach ($categoryIds as $cid) {
            $cid = (int)$cid;
            if ($cid <= 2) {
                continue; // skip Magento root (1) and default (2) categories
            }
            try {
                $name = trim((string)$this->categoryRepository->get($cid)->getName());
            } catch (\Throwable $e) {
                continue;
            }
            if ($name === '') {
                continue;
            }
            $odooId = $this->ensureOdooCategory($name);
            if ($odooId !== null) {
                return $odooId;
            }
        }

        return null;
    }

    private function ensureOdooCategory(string $name): ?int
    {
        if (isset($this->cache[$name])) {
            return $this->cache[$name];
        }
        try {
            $found = $this->odooClient->executeKw('product.category', 'search', [[['name', '=', $name]]], ['limit' => 1]);
            if (is_array($found) && isset($found[0])) {
                return $this->cache[$name] = (int)$found[0];
            }
            $id = (int)$this->odooClient->executeKw('product.category', 'create', [['name' => $name]]);

            return $this->cache[$name] = $id;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
