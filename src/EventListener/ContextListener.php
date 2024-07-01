<?php

namespace Ecosystem\ApiHelpersBundle\EventListener;

use Ecosystem\ApiHelpersBundle\Service\ContextService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class ContextListener
{
    private const LOCALE_HEADER = 'X-ECOSYS-LOCALE';
    private const ALLOWED_LOCALES = ['es', 'en', 'ca'];

    public function __construct(private readonly ContextService $contextService)
    {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $locale = $request->getLocale();

        if (
            $request->headers->has(self::LOCALE_HEADER)
            && in_array($request->headers->get(self::LOCALE_HEADER), self::ALLOWED_LOCALES, true)
        ) {
            $locale = $request->headers->get(self::LOCALE_HEADER);
            $request->setLocale($locale);
        }
        $this->contextService->setLocale($locale);
    }
}