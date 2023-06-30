<?php

namespace Ecosystem\ApiHelpersBundle\Service;

class ContextService
{
    private ?string $locale = null;

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(?string $locale): void
    {
        $this->locale = $locale;
    }
}