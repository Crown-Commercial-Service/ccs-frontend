<?php

declare(strict_types=1);

namespace App\Factory;

use Monolog\Handler\RollbarHandler;
use Monolog\Logger;
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
        Rollbar::init([
            'access_token' => $accessToken,
            'environment' => $environment,
        ], false, false, false);

        return new RollbarHandler(Rollbar::logger(), Logger::ERROR);
    }
}
