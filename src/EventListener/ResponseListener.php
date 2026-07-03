<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\ResponseEvent;

class ResponseListener
{
    protected string $appEnv;

    public function __construct(string $appEnv)
    {
        $this->appEnv = $appEnv;
    }

    /**
     * Alter response
     *
     * @param ResponseEvent $event
     */
    public function onKernelResponse(ResponseEvent $event)
    {
        $response = $event->getResponse();

        // Add caching layer for Production (5 min cache on all pages)
        if ($this->appEnv === 'prod') {
            $response->setSharedMaxAge(300);
            $response->headers->addCacheControlDirective('must-revalidate', true);
        }
    }
}
