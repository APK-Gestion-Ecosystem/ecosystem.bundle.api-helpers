<?php

namespace Ecosystem\ApiHelpersBundle\EventListener;

use Ecosystem\ApiHelpersBundle\Exception\ValidationException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

final class ExceptionListener
{
    private const INTERNAL_SERVER_ERROR = 'Internal Server Error.';

    public function __construct(private LoggerInterface $logger, private bool $debug = false)
    {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $this->logger->error(sprintf('Handling exception: %s', $exception->getMessage()));

        $response = $this->getResponseFromException($exception);
        $event->setResponse($response);
    }

    private function getResponseFromException(\Throwable $exception): JsonResponse
    {
        if ($exception instanceof ValidationException) {
            return new JsonResponse([
                'code' => $exception->getCode(),
                'message' => $exception->getMessage(),
                'violations' => $exception->getErrors()
            ], $exception->getCode());
        }

        if ($exception instanceof HttpExceptionInterface) {
            return new JsonResponse([
                'code' => $exception->getStatusCode(),
                'message' => $exception->getMessage(),
            ], $exception->getStatusCode());
        }

        return new JsonResponse([
            'code' => JsonResponse::HTTP_INTERNAL_SERVER_ERROR,
            'message' => $this->debug ? $exception->getMessage() : self::INTERNAL_SERVER_ERROR,
        ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
    }
}
