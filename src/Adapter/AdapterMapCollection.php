<?php

namespace Ecosystem\ApiHelpersBundle\Adapter;

#[\Attribute]
class AdapterMapCollection
{
    public function __construct(
        private string $class,
        private string $identificatorField = 'uuid'
    ) {
    }
}
