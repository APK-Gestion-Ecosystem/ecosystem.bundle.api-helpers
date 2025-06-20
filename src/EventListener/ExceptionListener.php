<?php

namespace Ecosystem\ApiHelpersBundle\EventListener;

use Ecosystem\ApiHelpersBundle\Exception\ValidationException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;

final class ExceptionListener
{
    private const INTERNAL_SERVER_ERROR = 'Internal Server Error.';

    public function __construct(private LoggerInterface $logger, private bool $debug = false)
    {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        $response = $this->getResponseFromException($exception);
        $event->setResponse($response);
    }

    private function getResponseFromException(\Throwable $exception): JsonResponse
    {
        if ($exception instanceof ValidationException) {
            $this->logger->debug(sprintf('Validation exception (%s): "%s"', $exception::class, $exception->getMessage()));
            return new JsonResponse([
                'code' => $exception->getCode(),
                'message' => $exception->getMessage(),
                'violations' => $exception->getErrors()
            ], $exception->getCode());
        }

        if ($exception instanceof HttpExceptionInterface) {
            if ($exception->getCode() < 500) {
                $this->logger->info(sprintf('Handling HTTP exception (%s): "%s"', $exception::class, $exception->getMessage()));
            } else {
                $this->logger->error(sprintf('Handling HTTP exception (%s): "%s"', $exception::class, $exception->getMessage()));
            }

            return new JsonResponse([
                'code' => $exception->getStatusCode(),
                'message' => $exception->getMessage(),
            ], $exception->getStatusCode());
        }

        if ($exception instanceof UnexpectedValueException) {
            $this->logger->warning(sprintf('Encoding issues (%s): "%s"', $exception::class, $exception->getMessage()));
            return new JsonResponse([
                'code' => 400,
                'message' => $exception->getMessage(),
            ], 400);
        }

        $this->logger->error(sprintf('Handling exception (%s): "%s"', $exception::class, $exception->getMessage()));

        return new JsonResponse([
            'code' => JsonResponse::HTTP_INTERNAL_SERVER_ERROR,
            'message' => $this->debug ? $exception->getMessage() : self::INTERNAL_SERVER_ERROR,
        ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
    }
}
