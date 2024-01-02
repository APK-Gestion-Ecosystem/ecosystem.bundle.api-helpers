<?php

namespace Ecosystem\ApiHelpersBundle\DTO;

#[\Attribute]
class DTO
{
    pubLic const DEFAULT_DESERIALIZATION_FORMAT = 'json';

    public function __construct(
        private string $class,
        private ?array $validationGroups = null,
        private ?string $deserializationFormat = null,
        private ?array $context = null
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

    public function getDeserializationFormat(): ?string
    {
        return $this->deserializationFormat;
    }

    public function setDeserializationFormat(?string $deserializationFormat): self
    {
        $this->deserializationFormat = $deserializationFormat;

        return $this;
    }

    public function getContext(): ?array
    {
        return $this->context;
    }

    public function setContext(?array $context): self
    {
        $this->context = $context;

        return $this;
    }
}
