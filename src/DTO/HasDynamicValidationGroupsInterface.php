<?php

namespace Ecosystem\ApiHelpersBundle\DTO;

interface HasDynamicValidationGroupsInterface
{
    public function getDynamicValidationGroups(): array;
}