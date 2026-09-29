<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppReturns\Model\Rma;

use Magento\Framework\GraphQl\Exception\GraphQlInputException;

/**
 * pageSize / currentPage of a list field, checked against the range its @doc promises.
 */
final class Paging
{
    private function __construct(
        public readonly int $pageSize,
        public readonly int $currentPage
    ) {
    }

    /**
     * @param array<string, mixed>|null $args the field's arguments
     * @throws GraphQlInputException when a value is outside the documented range
     */
    public static function fromArgs(?array $args, int $defaultSize, int $maxSize): self
    {
        $size = isset($args['pageSize']) ? (int) $args['pageSize'] : $defaultSize;
        $page = isset($args['currentPage']) ? (int) $args['currentPage'] : 1;
        if ($size < 1 || $size > $maxSize) {
            throw new GraphQlInputException(__('pageSize must be between 1 and %1.', $maxSize));
        }
        if ($page < 1) {
            throw new GraphQlInputException(__('currentPage must be 1 or more.'));
        }

        return new self($size, $page);
    }

    public function offset(): int
    {
        return ($this->currentPage - 1) * $this->pageSize;
    }

    /**
     * SearchResultPageInfo.
     *
     * @return array{page_size: int, current_page: int, total_pages: int}
     */
    public function pageInfo(int $totalCount): array
    {
        return [
            'page_size' => $this->pageSize,
            'current_page' => $this->currentPage,
            'total_pages' => $totalCount > 0 ? (int) ceil($totalCount / $this->pageSize) : 0,
        ];
    }
}
