<?php

namespace Ecosystem\ApiHelpersBundle\Adapter;

#[\Attribute]
class AdapterMapEntity
{
    public function __construct(
        private string $class,
        private string $identificatorField = 'uuid',
    ) {
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function setClass(string $class): AdapterMapEntity
    {
        $this->class = $class;
        return $this;
    }

    public function getIdentificatorField(): string
    {
        return $this->identificatorField;
    }

    public function setIdentificatorField(string $identificatorField): AdapterMapEntity
    {
        $this->identificatorField = $identificatorField;
        return $this;
    }
}
