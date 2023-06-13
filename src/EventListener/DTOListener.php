<?php

namespace Ecosystem\ApiHelpersBundle\EventListener;

use Ecosystem\ApiHelpersBundle\DTO\DTO;
use Ecosystem\ApiHelpersBundle\DTO\HasDynamicValidationGroupsInterface;
use Ecosystem\ApiHelpersBundle\Exception\ValidationException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DTOListener
{
    public function __construct(private ValidatorInterface $validator, private SerializerInterface $serializer)
    {
    }

    public function onKernelControllerArguments(ControllerArgumentsEvent $event): void
    {
        $controller = $event->getController();
        $reflectedMethod = $this->getReflectedMethod($controller);
        if ($reflectedMethod === null) {
            return;
        }

        $dtoClass = $this->getDtoClass($reflectedMethod->getAttributes());
        if ($dtoClass === null) {
            return;
        }

        $content = $event->getRequest()->getContent();
        if (empty($content)) {
            throw new \RuntimeException('Request content is empty');
        }

        $dto = $this->serializer->deserialize($content, $dtoClass, 'json');

        $validationGroups = $this->getValidationGroups($reflectedMethod->getAttributes());
        if ($dto instanceof HasDynamicValidationGroupsInterface) {
            $validationGroups = array_merge($validationGroups, $dto->getDynamicValidationGroups());
        }

        $validationErrors = $this->validator->validate($dto, groups: $validationGroups);
        if (count($validationErrors) > 0) {
            throw new ValidationException($validationErrors);
        }
        $newArguments = $this->getNewArguments($event->getArguments(), $dto);
        $event->setArguments($newArguments);
    }

    private function getReflectedMethod(callable $callable): ?\ReflectionMethod
    {
        if (is_array($callable)) {
            [$object, $method] = $callable;
            try {
                $reflectedClass = new \ReflectionClass($object);
                return $reflectedClass->getMethod($method);
            } catch (\ReflectionException) {
            }
        }
        if (is_object($callable)) {
            $reflectedClass = new \ReflectionClass($callable);
            try {
                return $reflectedClass->getMethod('__invoke');
            } catch (\ReflectionException) {
            }
        }
        return null;
    }

    /**
     * @param \ReflectionAttribute[] $attributes
     * @return string|null
     */
    private function getDtoClass(array $attributes): ?string
    {
        foreach ($attributes as $attribute) {
            if ($attribute->getName() === DTO::class && isset($attribute->getArguments()['class'])) {
                return $attribute->getArguments()['class'];
            }
        }
        return null;
    }

    /**
     * @param \ReflectionAttribute[] $attributes
     * @return string[]
     */
    private function getValidationGroups(array $attributes): array
    {
        $groups = ['Default'];
        foreach ($attributes as $attribute) {
            if ($attribute->getName() === DTO::class && isset($attribute->getArguments()['validationGroups'])) {
                $groups = array_merge($groups, $attribute->getArguments()['validationGroups']);
                break;
            }
        }
        return $groups;
    }

    private function getNewArguments(array $arguments, object $dto): array
    {
        $dtoClass = get_class($dto);
        foreach ($arguments as $key => $argument) {
            if ($argument instanceof $dtoClass) {
                $arguments[$key] = $dto;
            }
        }
        return $arguments;
    }
}
