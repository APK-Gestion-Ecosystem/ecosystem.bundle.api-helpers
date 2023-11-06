<?php

namespace Ecosystem\ApiHelpersBundle\EventListener;

use Ecosystem\ApiHelpersBundle\DTO\DTO;
use Ecosystem\ApiHelpersBundle\DTO\HasDynamicValidationGroupsInterface;
use Ecosystem\ApiHelpersBundle\Exception\ValidationException;
use Ecosystem\ApiHelpersBundle\Pagination\PaginatedController;
use Ecosystem\ApiHelpersBundle\Pagination\PaginationData;
use Ecosystem\ApiHelpersBundle\Pagination\PaginationDataFactory;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ControllerArgumentsListener
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

        $arguments = $event->getArguments();
        $dtoClass = $this->getDtoClass($reflectedMethod->getAttributes());
        if ($dtoClass !== null) {
            $arguments = $this->getDtoArguments($dtoClass, $event->getRequest(), $reflectedMethod->getAttributes(), $event->getArguments());
        }

        $arguments = $this->addPaginationData($event->getRequest(), $arguments);
        $event->setArguments($arguments);
    }

    private function getDtoArguments(string $dtoClass, Request $request, array $attributes, array $currentArguments): array
    {
        if (empty($request->getContent())) {
            throw new \RuntimeException('Request content is empty');
        }

        $deserializationFormat = $this->getDeserializationFormat($attributes);
        $dto = $this->serializer->deserialize($request->getContent(), $dtoClass, $deserializationFormat);

        $validationGroups = $this->getValidationGroups($attributes);
        if ($dto instanceof HasDynamicValidationGroupsInterface) {
            $validationGroups = array_merge($validationGroups, $dto->getDynamicValidationGroups());
        }

        $validationGroupHeader = $request->headers->get('X-Validation-Group');
        if ($validationGroupHeader !== null) {
            $validationGroups = [$validationGroupHeader];
        }

        $validationErrors = $this->validator->validate($dto, groups: $validationGroups);
        if (count($validationErrors) > 0) {
            throw new ValidationException($validationErrors);
        }

        return $this->getNewDtoArguments($currentArguments, $dto);
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

    private function addPaginationData(Request $request, array $arguments): array
    {
        foreach ($arguments as $key => $argument) {
            if (is_object($argument) && get_class($argument) === PaginationData::class) {
                $arguments[$key] = PaginationDataFactory::createFromRequest($request);
            }
        }
        return $arguments;
    }

    private function getDeserializationFormat(array $attributes): string
    {
        foreach ($attributes as $attribute) {
            if ($attribute->getName() === DTO::class && isset($attribute->getArguments()['deserializationFormat'])) {
                return $attribute->getArguments()['deserializationFormat'];
            }
        }
        return DTO::DEFAULT_DESERIALIZATION_FORMAT;
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

    private function getNewDtoArguments(array $arguments, object $dto): array
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
