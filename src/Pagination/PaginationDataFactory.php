<?php

namespace Ecosystem\ApiHelpersBundle\Pagination;

use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;

class PaginationDataFactory
{
    public static function createFromRequest(Request $request): PaginationData
    {
        $paginationData = new PaginationData();

        $pageQueryParam = (int) $request->query->get('page');
        $page = match ($pageQueryParam) {
            -1 => null,
            0 => PaginationData::DEFAULT_PAGE,
            default => $pageQueryParam
        };
        $paginationData->setPage($page);

        $limitQueryParam = (int) $request->query->get('limit');
        $limit = match ($limitQueryParam) {
            -1 => null,
            0 => PaginationData::DEFAULT_LIMIT,
            default => $pageQueryParam
        };
        $paginationData->setLimit($limit);

        $filters = [];
        $queryString = $request->getQueryString();
        if ($queryString !== null) {
            $filters = HeaderUtils::parseQuery($queryString);
        }
        $paginationData->setFilters($filters);

        return $paginationData;
    }
}