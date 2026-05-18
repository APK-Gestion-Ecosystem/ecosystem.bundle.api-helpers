<?php

declare(strict_types=1);

namespace Ecosystem\ApiHelpersBundle\Adapter;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Uid\Uuid;
use ReflectionAttribute;

class CollectionMappingHandler implements AdapterHandlerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PropertyAccessorInterface $propertyAccessor
    ) {
    }

    public function supports(ReflectionAttribute $attribute): bool
    {
        return $attribute->getName() === AdapterMapCollection::class;
    }

    public function handle(
        ReflectionAttribute $attribute,
        mixed $value,
        object $source,
        object $target,
        string $propertyName,
        GenericAdapter $adapter
    ): mixed {
        if ($value === null) {
            return null;
        }

        $arguments = $attribute->getArguments();
        $class = (string) $arguments['class'];
        $identificatorField = (string) ($arguments['identificatorField'] ?? 'uuid');
        $strategy = (string) ($arguments['strategy'] ?? AdapterMapCollection::DEFAULT_STRATEGY);

        $repository = $this->entityManager->getRepository($class);
        $objects = [];

        foreach ($value as $item) {
            if ($strategy === AdapterMapCollection::UUIDS_ARRAY_STRATEGY) {
                $entity = $repository->findOneBy([$identificatorField => $item]);
                if ($entity !== null) {
                    $objects[] = $entity;
                }
                continue;
            }

            if ($strategy === AdapterMapCollection::ENTITIES_COLLECTION_STRATEGY) {
                $identificatorValue = null;
                if ($this->propertyAccessor->isReadable($item, $identificatorField)) {
                    $identificatorValue = $this->propertyAccessor->getValue($item, $identificatorField);
                }

                if ($identificatorValue !== null) {
                    $entity = $repository->findOneBy([$identificatorField => $identificatorValue]);
                    if ($entity === null) {
                        throw new NotFoundHttpException(sprintf('%s not found with identificator %s', ucfirst($propertyName), $identificatorValue));
                    }
                    $adapter->map($item, $entity);
                } else {
                    $entity = new $class();
                    $adapter->map($item, $entity);
                    $this->propertyAccessor->setValue(
                        $entity,
                        $identificatorField,
                        $this->getDefaultIdentificatorFieldValue($identificatorField)
                    );
                }
                
                if ($entity !== null) {
                    $objects[] = $entity;
                }
                continue;
            }
        }

        return new ArrayCollection($objects);
    }

    private function getDefaultIdentificatorFieldValue(string $identificatorField): ?string
    {
        return match ($identificatorField) {
            'uuid' => Uuid::v7()->toRfc4122(),
            default => null
        };
    }
}
