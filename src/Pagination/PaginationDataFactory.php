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
        $request->query->remove('page');

        $limitQueryParam = (int) $request->query->get('limit');
        $limit = match ($limitQueryParam) {
            -1 => null,
            0 => PaginationData::DEFAULT_LIMIT,
            default => $limitQueryParam
        };
        $paginationData->setLimit($limit);
        $request->query->remove('limit');

        $filters = [];
        if ($request->query->count() > 0) {
            foreach ($request->query->all() as $key => $value) {
                $filters[$key] = self::parseBooleanString($value);
            }
        }
        $paginationData->setFilters($filters);

        return $paginationData;
    }

    private static function parseBooleanString(string $value): mixed
    {
        if ($value === 'true')
            return true;
        elseif ($value === 'false')
            return false;
        else
            return $value;
    }
}