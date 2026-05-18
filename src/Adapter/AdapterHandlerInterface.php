<?php

declare(strict_types=1);

namespace Ecosystem\ApiHelpersBundle\Adapter;

use ReflectionAttribute;

interface AdapterHandlerInterface
{
    /**
     * Checks if this handler supports the given attribute.
     */
    public function supports(ReflectionAttribute $attribute): bool;

    /**
     * Processes the mapping for the given attribute.
     *
     * @param ReflectionAttribute $attribute The attribute found on the property.
     * @param mixed $value The current value of the property from the source.
     * @param object $source The source object.
     * @param object $target The target object.
     * @param string $propertyName The name of the property being mapped.
     * @param GenericAdapter $adapter The adapter instance (useful for recursive mapping).
     * @return mixed The transformed value to be set in the target object.
     */
    public function handle(
        ReflectionAttribute $attribute,
        mixed $value,
        object $source,
        object $target,
        string $propertyName,
        GenericAdapter $adapter
    ): mixed;
}
