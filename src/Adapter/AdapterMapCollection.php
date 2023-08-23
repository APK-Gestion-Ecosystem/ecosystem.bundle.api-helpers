<?php

namespace Ecosystem\ApiHelpersBundle\Adapter;

#[\Attribute]
class AdapterMapCollection
{
    public const UUIDS_ARRAY_STRATEGY = 'uuids_array';
    public const ENTITIES_COLLECTION_STRATEGY = 'entities_collection';
    public const DEFAULT_STRATEGY = self::ENTITIES_COLLECTION_STRATEGY;

    public function __construct(
        private string $class,
        private string $identificatorField = 'uuid',
        private string $strategy = self::DEFAULT_STRATEGY
    ) {
    }
}
