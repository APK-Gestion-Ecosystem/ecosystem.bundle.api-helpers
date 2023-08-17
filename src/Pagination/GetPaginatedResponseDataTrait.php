<?php

namespace Ecosystem\ApiHelpersBundle\Pagination;

trait GetPaginatedResponseDataTrait
{
    private function getPaginatedResponseData(array $items, int $total, ?int $limit, ?int $page): array
    {
        return [
            'items' => $items,
            'total' => $total,
            'limit' => $limit,
            'page' => $page ?? 1,
            'pages' => $limit !== null ? (int) ceil($total / $limit) : 1,
        ];
    }
}