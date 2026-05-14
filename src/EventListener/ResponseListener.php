<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\ResponseEvent;

class ResponseListener
{
    /**
     * Alter response
     *
     * @param ResponseEvent $event
     */
    public function onKernelResponse(ResponseEvent $event)
    {
        $response = $event->getResponse();

        // Add caching layer for Production (30 min cache on all pages)
        if (getenv('APP_ENV') === 'prod') {
            $response->setSharedMaxAge(300);
            $response->headers->addCacheControlDirective('must-revalidate', true);
        }
    }
}
