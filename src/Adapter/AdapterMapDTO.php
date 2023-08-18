<?php

namespace Ecosystem\ApiHelpersBundle\Adapter;

#[\Attribute]
class AdapterMapDTO
{
    public function __construct(
        private string $class
    ) {
    }
}
