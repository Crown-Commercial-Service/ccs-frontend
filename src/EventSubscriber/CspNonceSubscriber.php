<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class CspNonceSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST  => ['onKernelRequest', 256],
            KernelEvents::RESPONSE => ['onKernelResponse', -256],
        ];
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        // Generate a secure nonce per request
        $nonce = rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '=');
        $request->attributes->set('_csp_nonce', $nonce);
    }

    public function onKernelResponse(ResponseEvent $event)
    {
        $request  = $event->getRequest();
        $response = $event->getResponse();

        if (!$request->attributes->has('_csp_nonce')) {
            return;
        }

        $nonce = $request->attributes->get('_csp_nonce');

        $policy = "default-src blob:; " .
                "img-src 'self' https://www.googletagmanager.com https://www.google-analytics.com https://www.google.co.uk https://www.google.com px.ads.linkedin.com; " .
                "script-src 'self' 'nonce-{$nonce}' https://www.googletagmanager.com https://tagmanager.google.com " .
                    "https://www.google-analytics.com region1.analytics.google.com region2.analytics.google.com googleads.g.doubleclick.net snap.licdn.com " .
                    "https://www.google.co.uk ssl.google-analytics.com stats.g.doubleclick.net cdn.linkedin cdn2.gbqofs.com report.crown-comm.gbqofs.io; " .
                "style-src 'self' 'nonce-{$nonce}' https://fonts.googleapis.com tagmanager.google.com; " .
                "connect-src https://www.google-analytics.com https://www.google.co.uk region1.analytics.google.com region2.analytics.google.com " .
                    "cdn.linkedin stats.g.doubleclick.net cdn2.gbqofs.com report.crown-comm.gbqofs.io; " .
                "font-src https://fonts.gstatic.com; " .
                "frame-src 'self' https://www.googletagmanager.com; " .
                "object-src 'none'; " .
                "report-to https://neypx8roc8.execute-api.eu-west-2.amazonaws.com/prod/report";

        // Start in Report-Only for testing
        if (getenv('APP_ENV') == 'test') {
            $response->headers->set('Content-Security-Policy-Report-Only', $policy);
        }
    }
}
