<?php

namespace Ecosystem\ApiHelpersBundle\Adapter;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;

class GenericAdapter
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PropertyAccessorInterface $propertyAccessor
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

                // check if entity mapping is needed
                $adapterMapEntityAttribute = $this->getAdapterMapEntityAttribute($source::class, $propertyName);
                if ($adapterMapEntityAttribute !== null) {
                    $mapObject = $this->propertyAccessor->getValue($source, $propertyName);
                    if ($mapObject === null) {
                        continue;
                    }
                    $value = $this->mapEntityValue($adapterMapEntityAttribute, $mapObject, $propertyName);
                }

                // set value
                $this->propertyAccessor->setValue($target, $propertyName, $value);
            }
        }
    }

    private function getAdapterMapEntityAttribute(string $sourceClass, string $propertyName): ?\ReflectionAttribute
    {
        $propertyReflection = new \ReflectionProperty($sourceClass, $propertyName);
        $mapAttributes = $propertyReflection->getAttributes(AdapterMapEntity::class);
        return !empty($mapAttributes) ? $mapAttributes[0] : null;
    }

    private function mapEntityValue(\ReflectionAttribute $adapterMapClassAttribute, object $mapObject, string $propertyName): mixed
    {
        /** @var class-string $class */
        $class = (string) $adapterMapClassAttribute->getArguments()['class'];
        $identificatorField = (string) $adapterMapClassAttribute->getArguments()['identificatorField'];
        $strategy = isset($adapterMapClassAttribute->getArguments()['strategy'])
            ?  (string) $adapterMapClassAttribute->getArguments()['strategy']
            : AdapterMapEntity::DEFAULT_STRATEGY;

        $identificatorValue = $this->propertyAccessor->getValue(
            $mapObject,
            $identificatorField
        );
        $entity = $this->entityManager->getRepository($class)->findOneBy([
            $identificatorField => $identificatorValue
        ]);
        if ($entity === null) {
            if ($strategy === AdapterMapEntity::STRICT_STRATEGY) {
                throw new NotFoundHttpException(sprintf('%s not found with identificator %s', ucfirst($propertyName), $identificatorValue));
            }
            if ($strategy === AdapterMapEntity::PERSIST_STRATEGY) {
                $entity = new $class();
                $this->map($mapObject, $entity);
            }
        }
        return $entity;
    }
}
