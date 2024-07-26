<?php

namespace Ecosystem\ApiHelpersBundle\Adapter;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Uid\Uuid;

class GenericAdapter
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected PropertyAccessorInterface $propertyAccessor
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
                    $value = $mapObject !== null ? $this->mapEntityValue($adapterMapEntityAttribute, $mapObject, $propertyName) : null;
                }

                // check if collection mapping is needed
                $adapterMapCollectionAttribute = $this->getAdapterMapCollectionAttribute($source::class, $propertyName);
                if ($adapterMapCollectionAttribute !== null) {
                    $mapObject = $this->propertyAccessor->getValue($source, $propertyName);
                    $value = $mapObject !== null ? $this->mapCollectionValue($adapterMapCollectionAttribute, $mapObject, $propertyName, $target) : null;
                }

                // set value
                $this->propertyAccessor->setValue($target, $propertyName, $value);
            }
        }
    }

    protected function getAdapterMapEntityAttribute(string $sourceClass, string $propertyName): ?\ReflectionAttribute
    {
        $propertyReflection = new \ReflectionProperty($sourceClass, $propertyName);
        $mapAttributes = $propertyReflection->getAttributes(AdapterMapEntity::class);
        return !empty($mapAttributes) ? $mapAttributes[0] : null;
    }

    protected function getAdapterMapCollectionAttribute(string $sourceClass, string $propertyName): ?\ReflectionAttribute
    {
        $propertyReflection = new \ReflectionProperty($sourceClass, $propertyName);
        $mapAttributes = $propertyReflection->getAttributes(AdapterMapCollection::class);
        return !empty($mapAttributes) ? $mapAttributes[0] : null;
    }

    protected function mapEntityValue(\ReflectionAttribute $adapterMapClassAttribute, object $mapObject, string $propertyName): mixed
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

    protected function mapCollectionValue(
        \ReflectionAttribute $adapterMapClassAttribute,
        array $mapObject,
        string $propertyName,
        object $target
    ): ArrayCollection {
        $class = (string) $adapterMapClassAttribute->getArguments()['class'];
        $identificatorField = (string) $adapterMapClassAttribute->getArguments()['identificatorField'];
        $strategy = isset($adapterMapClassAttribute->getArguments()['strategy'])
            ?  (string) $adapterMapClassAttribute->getArguments()['strategy']
            : AdapterMapCollection::DEFAULT_STRATEGY;

        $repository = $this->entityManager->getRepository($class);

        $objects = [];
        foreach ($mapObject as $item) {
            if ($strategy === AdapterMapCollection::UUIDS_ARRAY_STRATEGY) {
                $entity = $repository->findOneBy([$identificatorField => $item]);
                if ($entity !== null) {
                    $objects[] = $entity;
                }
                continue;
            }

            if (in_array($strategy, [AdapterMapCollection::ENTITIES_COLLECTION_STRATEGY, AdapterMapCollection::ENTITIES_COLLECTION_PERSIST_STRATEGY])) {
                $identificatorValue = $this->propertyAccessor->getValue($item, $identificatorField);
                if ($identificatorValue !== null) {
                    $value = $repository->findOneBy([$identificatorField => $identificatorValue]);
                    if ($value === null) {
                        if ($strategy === AdapterMapCollection::ENTITIES_COLLECTION_STRATEGY) {
                            throw new NotFoundHttpException(sprintf('%s not found with identificator %s', ucfirst($propertyName), $identificatorValue));
                        } elseif ($strategy === AdapterMapCollection::ENTITIES_COLLECTION_PERSIST_STRATEGY) {
                            $value = new $class();
                            $this->propertyAccessor->setValue(
                                $value,
                                $identificatorField,
                                $this->getDefaultIdentificatorFieldValue($identificatorField)
                            );
                        }
                    }
                    $this->map($item, $value);
                } else {
                    $value = new $class();
                    $this->map($item, $value);
                    $this->propertyAccessor->setValue(
                        $value,
                        $identificatorField,
                        $this->getDefaultIdentificatorFieldValue($identificatorField)
                    );
                }
                if ($value !== null) {
                    $objects[] = $value;
                }
                continue;
            }
        }
        return new ArrayCollection($objects);
    }

    protected function getDefaultIdentificatorFieldValue(string $identificatorField): string
    {
        return match ($identificatorField) {
            'uuid' => Uuid::v7()->toRfc4122(),
            default => null
        };
    }
}
