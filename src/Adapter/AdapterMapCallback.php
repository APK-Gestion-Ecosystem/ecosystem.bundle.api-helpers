<?php

declare(strict_types=1);

namespace Ecosystem\ApiHelpersBundle\Adapter;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class AdapterMapCallback
{
    /**
     * @param callable-string|array{0: class-string, 1: string} $callback
     */
    public function __construct(
        private string|array $callback
    ) {
    }

    public function getCallback(): string|array
    {
        return $this->callback;
    }
}
