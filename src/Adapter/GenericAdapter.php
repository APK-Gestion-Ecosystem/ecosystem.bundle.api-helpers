<?php

declare(strict_types=1);

namespace Ecosystem\ApiHelpersBundle\Adapter;

use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;

class GenericAdapter
{
    /**
     * @param iterable<AdapterHandlerInterface> $handlers
     */
    public function __construct(
        private PropertyAccessorInterface $propertyAccessor,
        private iterable $handlers
    ) {
    }

    public function map(object $source, object $target): void
    {
        $reflectionExtractor = new ReflectionExtractor();
        $sourceProperties = $reflectionExtractor->getProperties($source::class);

        if (!is_array($sourceProperties)) {
            throw new \RuntimeException('Error when mapping objects');
        }

        foreach ($sourceProperties as $propertyName) {
            if ($this->propertyAccessor->isWritable($target, $propertyName) && $this->propertyAccessor->isReadable($source, $propertyName)) {
                $value = $this->propertyAccessor->getValue($source, $propertyName);

                $propertyReflection = new \ReflectionProperty($source::class, $propertyName);
                $attributes = $propertyReflection->getAttributes();

                foreach ($attributes as $attribute) {
                    foreach ($this->handlers as $handler) {
                        if ($handler->supports($attribute)) {
                            $value = $handler->handle($attribute, $value, $source, $target, $propertyName, $this);
                            break;
                        }
                    }
                }

                $this->propertyAccessor->setValue($target, $propertyName, $value);
            }
        }
    }
}
