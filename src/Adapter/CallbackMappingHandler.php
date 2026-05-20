<?php

declare(strict_types=1);

namespace Ecosystem\ApiHelpersBundle\Adapter;

use ReflectionAttribute;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CallbackMappingHandler implements AdapterHandlerInterface
{
    public function __construct(
        private ContainerInterface $container
    ) {}

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

        if ($callback === null) {
            throw new \RuntimeException(sprintf(
                'Invalid callback for property %s',
                $propertyName
            ));
        }

        if (is_array($callback) && isset($callback[0], $callback[1])) {
            $service = $this->container->get($callback[0]);
            $method = $callback[1];

            return $service->$method(
                $value,
                $source,
                $target,
                $propertyName
            );
        }

        if (is_callable($callback)) {
            return $callback(
                $value,
                $source,
                $target,
                $propertyName
            );
        }

        throw new \RuntimeException(sprintf(
            'Invalid callback for property %s',
            $propertyName
        ));
    }
}
