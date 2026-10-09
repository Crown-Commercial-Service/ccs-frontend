<?php

declare(strict_types=1);

namespace App\Factory;

use Monolog\Handler\RollbarHandler;
use Monolog\Level;
use Rollbar\Rollbar;

/**
 * Builds the Monolog handler that reports errors to Rollbar.
 *
 * Replaces rollbar/rollbar-php-symfony-bundle, which does not support
 * Symfony 7 (latest release caps at ^6.2). Integrates directly against
 * the core rollbar/rollbar SDK and Monolog's own native RollbarHandler.
 */
class RollbarHandlerFactory
{
    public static function create(string $accessToken, string $environment): RollbarHandler
    {
        $isLocalOrDev = in_array(strtolower($environment), ['dev', 'local', 'development'], true);
        $isDummy = ($accessToken === '' || $accessToken === '11111111111111111111111111111111' || strlen($accessToken) < 32);
        $enabled = !$isLocalOrDev && !$isDummy;
        $token = $isDummy ? '00000000000000000000000000000000' : $accessToken;

        Rollbar::init([
            'access_token' => $token,
            'environment' => $environment,
            'enabled' => $enabled,
        ], false, false, false);

        return new RollbarHandler(Rollbar::logger(), Level::Error);
    }
}
