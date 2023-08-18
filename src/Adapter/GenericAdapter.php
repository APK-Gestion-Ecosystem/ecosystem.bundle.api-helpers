<?php

namespace Ecosystem\ApiHelpersBundle\Adapter;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Proxy;
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

        $mapToEntity = $this->isEntity($target);

        foreach ($sourceProperties as $propertyName) {
            if ($this->propertyAccessor->isWritable($target, $propertyName) && $this->propertyAccessor->isReadable($source, $propertyName)) {
                $value = $this->propertyAccessor->getValue($source, $propertyName);

                if ($mapToEntity) {
                    // check if entity mapping is needed
                    $adapterMapEntityAttribute = $this->getAdapterMapEntityAttribute($source::class, $propertyName);
                    if ($adapterMapEntityAttribute !== null) {
                        $mapObject = $this->propertyAccessor->getValue($source, $propertyName);
                        $value = $mapObject !== null ? $this->mapEntityValue($adapterMapEntityAttribute, $mapObject, $propertyName) : null;
                    }

                    // check if collection mapping is needed
                    $adapterMapCollectionAttribute = $this->getAdapterMapCollectionAttribute($source::class, $propertyName);
                    if ($adapterMapCollectionAttribute !== null) {
                        $mapObject = $this->propertyAccessor->getValue($source, $propertyName);
                        $value = $mapObject !== null ? $this->mapCollectionValue($adapterMapCollectionAttribute, $mapObject, $propertyName) : null;
                    }
                } else {
                    // check if dto mapping is needed
                    $adapterMapDTOAttribute = $this->getAdapterMapDTOAttribute($target::class, $propertyName);
                    if ($adapterMapDTOAttribute !== null) {
                        $mapObject = $this->propertyAccessor->getValue($source, $propertyName);
                        $value = $mapObject !== null ? $this->mapDTOValue($adapterMapDTOAttribute, $mapObject): null;
                    }
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

    private function getAdapterMapCollectionAttribute(string $sourceClass, string $propertyName): ?\ReflectionAttribute
    {
        $propertyReflection = new \ReflectionProperty($sourceClass, $propertyName);
        $mapAttributes = $propertyReflection->getAttributes(AdapterMapCollection::class);
        return !empty($mapAttributes) ? $mapAttributes[0] : null;
    }

    private function getAdapterMapDTOAttribute(string $targetClass, $propertyName): ?\ReflectionAttribute
    {
        $propertyReflection = new \ReflectionProperty($targetClass, $propertyName);
        $mapAttributes = $propertyReflection->getAttributes(AdapterMapDTO::class);
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

    private function mapCollectionValue(\ReflectionAttribute $adapterMapClassAttribute, array $mapObject, string $propertyName): ArrayCollection
    {
        $class = (string) $adapterMapClassAttribute->getArguments()['class'];
        $identificatorField = (string) $adapterMapClassAttribute->getArguments()['identificatorField'];

        $repository = $this->entityManager->getRepository($class);

        $objects = [];
        foreach ($mapObject as $identificatorValue) {
            $entity = $repository->findOneBy([$identificatorField => $identificatorValue]);
            if ($entity !== null) {
                $objects[] = $entity;
            }
        }
        return new ArrayCollection($objects);
    }

    private function mapDTOValue(\ReflectionAttribute $adapterMapDTOAttribute, object $mapObject): ?object
    {
        $class = $adapterMapDTOAttribute->getArguments()['class'];
        $dto = new $class();
        $this->map($mapObject, $dto);
        return $dto;
    }

    private function isEntity(object $class): bool
    {
        if (is_object($class)) {
            $class = ($class instanceof Proxy)
                ? get_parent_class($class)
                : get_class($class);
        }

        return ! $this->entityManager->getMetadataFactory()->isTransient($class);
    }
}
