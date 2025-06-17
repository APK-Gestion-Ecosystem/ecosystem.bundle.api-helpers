<?php

namespace Ecosystem\ApiHelpersBundle\Adapter;

#[\Attribute]
class AdapterMapEntity
{
    public const STRICT_STRATEGY = 'strict';
    public const PERSIST_STRATEGY = 'persist';
    public const EARLY_PERSIST_STRATEGY = 'early_persist';
    public const DEFAULT_STRATEGY = self::STRICT_STRATEGY;

    public function __construct(
        private string $class,
        private string $identificatorField = 'uuid',
        private string $strategy = self::DEFAULT_STRATEGY
    ) {
    }
}
