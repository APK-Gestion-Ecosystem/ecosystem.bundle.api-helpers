<?php

namespace Ecosystem\ApiHelpersBundle\DTO;

#[\Attribute]
class DTO
{
    public function __construct(private string $class)
    {
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function setClass(string $class): void
    {
        $this->class = $class;
    }
}
