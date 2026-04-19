<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * CORS without Nelmio: short-circuit OPTIONS preflights, then add Allow-Origin on real responses.
 */
final class CorsSubscriber implements EventSubscriberInterface
{
    private const ALLOW_METHODS = 'GET, OPTIONS, POST, PUT, PATCH, DELETE';

    private const DEFAULT_ALLOW_HEADERS = 'Content-Type, Authorization';

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 256],
            KernelEvents::RESPONSE => ['onKernelResponse', -1024],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (HttpKernelInterface::MAIN_REQUEST !== $event->getRequestType()) {
            return;
        }

        $request = $event->getRequest();
        if (Request::METHOD_OPTIONS !== $request->getMethod()) {
            return;
        }

        if (!$this->isCorsPreflightRequest($request)) {
            return;
        }

        $response = new Response('', Response::HTTP_NO_CONTENT);
        $this->applyPreflightHeaders($request, $response);
        $event->setResponse($response);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (HttpKernelInterface::MAIN_REQUEST !== $event->getRequestType()) {
            return;
        }

        $event->getResponse()->headers->set('Access-Control-Allow-Origin', '*');
    }

    private function isCorsPreflightRequest(Request $request): bool
    {
        return $request->headers->has('Access-Control-Request-Method')
            || $request->headers->has('Access-Control-Request-Private-Network');
    }

    private function applyPreflightHeaders(Request $request, Response $response): void
    {
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', self::ALLOW_METHODS);

        $requested = $request->headers->get('Access-Control-Request-Headers');
        $response->headers->set(
            'Access-Control-Allow-Headers',
            $requested !== null && $requested !== '' ? $requested : self::DEFAULT_ALLOW_HEADERS,
        );

        $response->headers->set('Access-Control-Max-Age', '3600');
        $response->headers->set('Vary', 'Origin');
    }
}
