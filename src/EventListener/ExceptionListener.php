<?php

namespace Ecosystem\ApiHelpersBundle\EventListener;

use Psr\Log\LoggerInterface;
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
        $statusCode = JsonResponse::HTTP_INTERNAL_SERVER_ERROR;
        $statusMessage = $this->debug ? $exception->getMessage() : self::INTERNAL_SERVER_ERROR;

        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
            $statusMessage = $exception->getMessage();
        } else {
            $this->logger->error(sprintf('Unhandled exception: %s', $exception->getMessage()));
        }

        $response = new JsonResponse([
            'code' => $statusCode,
            'message' => $statusMessage,
        ], $statusCode);

        $event->setResponse($response);
    }
}
