<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class RobotsTagSubscriber implements EventSubscriberInterface
{
    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $host = $event->getRequest()->getHost();

        $blockedHosts = [
            'webprod.crowncommercial.gov.uk',
            'webprod.gca.gov.uk'
        ];

        // If the current host matches list, attach this header to the response to prevent indexing by search engines
        if (\in_array($host, $blockedHosts, true)) {
            $response = $event->getResponse();
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }
}
