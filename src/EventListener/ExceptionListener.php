<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelInterface;

final class ExceptionListener
{
    public function __construct(
        private readonly KernelInterface $kernel
    ) {
    }

    #[AsEventListener]
    public function onExceptionEvent(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        // Use exception code as HTTP status code if it's a valid HTTP status code (100-599)
        // Otherwise default to 500
        $statusCode = $exception->getCode();
        if ($statusCode < 100 || $statusCode > 599) {
            $statusCode = 500;
        }

        $responseData = [];
        $responseData['message'] = $exception->getMessage();
        $responseData['stacktrace'] = $exception->getTraceAsString();
        $responseData['code'] = $exception->getCode();

        $response = new JsonResponse($responseData, $statusCode);
        $event->setResponse($response);
    }
}
