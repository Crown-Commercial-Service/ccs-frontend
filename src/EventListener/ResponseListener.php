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

        // @todo remove this in Symfony 4.3 - see https://symfony.com/blog/new-in-symfony-4-3-automatic-search-engine-protection
        if (getenv('APP_ENV') !== 'prod') {
            $response->headers->add(['X-Robots-Tag' => 'noindex']);
        }

        // Add caching layer for Production (30 min cache on all pages)
        if (getenv('APP_ENV') === 'prod') {
            $response->setSharedMaxAge(300);
            $response->headers->addCacheControlDirective('must-revalidate', true);
        }
    }
}
