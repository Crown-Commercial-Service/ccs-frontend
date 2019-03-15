<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class ResponseListener
{
    /**
     * Add noindex header if not on prod
     * @todo remove this in Symfony 4.3 - see https://symfony.com/blog/new-in-symfony-4-3-automatic-search-engine-protection
     *
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if (getenv('APP_ENV') !== 'prod') {
            $response = $event->getResponse();
            $response->headers->add(['X-Robots-Tag' => 'noindex']);
        }
    }

}