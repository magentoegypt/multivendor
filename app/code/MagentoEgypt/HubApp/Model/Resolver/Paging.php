<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Resolver;

use Magento\Framework\GraphQl\Exception\GraphQlInputException;

/**
 * pageSize / currentPage of the app's list queries, and SearchResultPageInfo.
 *
 * A page size above the list's maximum is lowered to it (the app asks for "a
 * page", not for an error); below 1, or a current page below 1, is a client
 * error, as in core's products query. A page past the end is simply empty.
 */
final class Paging
{
    private function __construct()
    {
    }

    /**
     * @param array<string, mixed>|null $args
     * @return array{0: int, 1: int} [pageSize, currentPage]
     * @throws GraphQlInputException
     */
    public static function args(?array $args, int $defaultSize, int $maxSize): array
    {
        $pageSize = isset($args['pageSize']) ? (int) $args['pageSize'] : $defaultSize;
        $currentPage = isset($args['currentPage']) ? (int) $args['currentPage'] : 1;
        if ($pageSize < 1) {
            throw new GraphQlInputException(__('pageSize must be greater than 0.'));
        }
        if ($currentPage < 1) {
            throw new GraphQlInputException(__('currentPage must be greater than 0.'));
        }

        return [min($pageSize, $maxSize), $currentPage];
    }

    /**
     * @return array{page_size: int, current_page: int, total_pages: int}
     */
    public static function info(int $total, int $pageSize, int $currentPage): array
    {
        return [
            'page_size' => $pageSize,
            'current_page' => $currentPage,
            'total_pages' => $pageSize > 0 ? (int) ceil($total / $pageSize) : 0,
        ];
    }

    /**
     * The slice of $items for the page.
     *
     * @template T
     * @param array<int, T> $items
     * @return array<int, T>
     */
    public static function slice(array $items, int $pageSize, int $currentPage): array
    {
        return array_slice(array_values($items), ($currentPage - 1) * $pageSize, $pageSize);
    }
}
