<?php

declare(strict_types=1);

namespace Ecosystem\ApiHelpersBundle\Adapter;

use ReflectionAttribute;

class CallbackMappingHandler implements AdapterHandlerInterface
{
    public function supports(ReflectionAttribute $attribute): bool
    {
        return $attribute->getName() === AdapterMapCallback::class;
    }

    public function handle(
        ReflectionAttribute $attribute,
        mixed $value,
        object $source,
        object $target,
        string $propertyName,
        GenericAdapter $adapter
    ): mixed {
        $arguments = $attribute->getArguments();
        $callback = $arguments['callback'] ?? null;

        if ($callback === null || !is_callable($callback)) {
            throw new \RuntimeException(sprintf('Invalid callback for property %s', $propertyName));
        }

        return $callback($value, $source, $target, $propertyName);
    }
}
