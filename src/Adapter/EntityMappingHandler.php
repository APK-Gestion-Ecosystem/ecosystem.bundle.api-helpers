<?php

declare(strict_types=1);

namespace Ecosystem\ApiHelpersBundle\Adapter;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use ReflectionAttribute;

class EntityMappingHandler implements AdapterHandlerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PropertyAccessorInterface $propertyAccessor
    ) {
    }

    public function supports(ReflectionAttribute $attribute): bool
    {
        return $attribute->getName() === AdapterMapEntity::class;
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
        /** @var class-string $class */
        $class = (string) $arguments['class'];
        $identificatorField = (string) ($arguments['identificatorField'] ?? 'uuid');
        $strategy = (string) ($arguments['strategy'] ?? AdapterMapEntity::DEFAULT_STRATEGY);

        $identificatorValue = $this->propertyAccessor->getValue($value, $identificatorField);
        $entity = $this->entityManager->getRepository($class)->findOneBy([
            $identificatorField => $identificatorValue
        ]);

        if ($entity === null) {
            if ($strategy === AdapterMapEntity::STRICT_STRATEGY) {
                throw new NotFoundHttpException(sprintf('%s not found with identificator %s', ucfirst($propertyName), $identificatorValue));
            }
            if ($strategy === AdapterMapEntity::PERSIST_STRATEGY) {
                $entity = new $class();
                $adapter->map($value, $entity);
            }
            if ($strategy === AdapterMapEntity::EARLY_PERSIST_STRATEGY) {
                $entity = new $class();
                $adapter->map($value, $entity);
                $this->entityManager->persist($entity);
                $this->entityManager->flush();
            }
        }

        return $entity;
    }
}
