<?php

namespace Ecosystem\ApiHelpersBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

final class HealthCheckController extends AbstractController
{
    public function __construct(private string $build)
    {
    }

    #[Route('/health-check', methods: 'GET')]
    public function healthCheck(): JsonResponse
    {
        return new JsonResponse([
            'status' => 'ok',
            'build' => $this->build,
        ], JsonResponse::HTTP_OK);
    }
}
