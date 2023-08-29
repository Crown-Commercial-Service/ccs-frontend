<?php

declare(strict_types=1);

namespace App\Utils;

use Symfony\Component\Yaml\Yaml;

class EnergySolutionToolDecision
{
    protected static function loadConfig()
    {
        static $config;
        if (is_array($config)) {
            return $config;
        }

        $config = Yaml::parse(file_get_contents(__DIR__ . '/../../config/energy/energy_decision.yaml'));
        return $config;
    }

    public static function getDecision(int $id): array
    {
        $config = self::loadConfig();

        return isset($config[$id]) ? $config[$id] : [];
    }
}
