<?php

namespace Ecosystem\ApiHelpersBundle\DTO;

#[\Attribute]
class DTO
{
    public function __construct(
        private string $class,
        private ?array $validationGroups = null
    ) {
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function setClass(string $class): void
    {
        $this->class = $class;
    }

    public function getValidationGroups(): ?array
    {
        return $this->validationGroups;
    }
    
    public function setValidationGroups(?array $validationGroups): self
    {
        $this->validationGroups = $validationGroups;

        return $this;
    }
}
