<?php

declare(strict_types=1);

namespace App\Utils;

use Symfony\Component\Yaml\Yaml;

class FrameworkCategories
{
    /**
     * Load categories config file
     */
    protected static function loadConfig()
    {
        static $config;
        if (is_array($config)) {
            return $config;
        }

        $config = Yaml::parse(file_get_contents(__DIR__ . '/../../config/categories.yaml'));
        return $config;
    }

    /**
     * Return A-Z array of all categories (category name => category URL slug)
     *
     * @param string $pillar
     * @return array
     */
    public static function getAll(): array
    {
        $data = [];
        $config = self::loadConfig();

        foreach ($config['pillars'] as $pillar) {
            foreach ($pillar['categories'] as $category) {
                $data[$category['name']] = $category['slug'];
            }
        }
        ksort($data);

        return $data;
    }


    /**
     * Return array of pillars
     *
     * @return array
     */
    public static function getAllPillars(): array
    {
        return self::loadConfig();
    }

    /**
     * Return amounts of pillars
     *
     * @return int
     */
    public static function getAllPillarSize(): int
    {
        return count(self::loadConfig()["pillars"]);
    }

    /**
     * Return amounts of categories
     *
     * @return int
     */
    public static function getAllCategorySize(): int
    {
        $amount = 0;
        $config = self::loadConfig();

        foreach ($config['pillars'] as $pillar) {
            foreach ($pillar['categories'] as $category) {
                $amount++;
            }
        }

        return $amount;
    }


    /**
     * Return array of categories for a pillar (category name => category URL slug)
     *
     * Returns an empty array on no results
     *
     * @param string $name Pillar name
     * @return array
     */
    public static function getAllByPillar(string $name): array
    {
        $data = [];
        $config = self::loadConfig();

        foreach ($config['pillars'] as $pillar) {
            if ($pillar['name'] === $name) {
                foreach ($pillar['categories'] as $category) {
                    $data[$category['name']] = $category['slug'];
                }
                return $data;
            }
        }
        return [];
    }

    /**
     * Match either a pillar or category and return its data
     *
     * @param string $name
     * @return array|null
     */
    public static function find(string $name): ?array
    {
        $config = self::loadConfig();
        foreach ($config['pillars'] as $pillar) {
            if ($pillar['name'] === $name) {
                return $pillar;
            }

            foreach ($pillar['categories'] as $category) {
                if ($category['name'] === $name) {
                    return $category;
                }
            }
        }
        return null;
    }

    /**
     * Match either a pillar or category and return its data
     *
     * @param string $slug
     * @return array|null
     */
    public static function findBySlug(string $slug): ?array
    {
        $config = self::loadConfig();
        foreach ($config['pillars'] as $pillar) {
            if ($pillar['slug'] === $slug) {
                return $pillar;
            }

            foreach ($pillar['categories'] as $category) {
                if ($category['slug'] === $slug) {
                    return $category;
                }
            }
        }
        return null;
    }

    /**
     * Return the category name for a given category slug
     *
     * Returns null on no results
     *
     * @param string $slug Pillar or category slug
     * @return string|null
     */
    public static function getNameBySlug(string $slug): ?string
    {
        $category = self::findBySlug($slug);
        if ($category === null) {
            return null;
        }

        return $category['name'];
    }

    /**
     * Return the category slug for a given category name
     *
     * Returns null on no results
     *
     * @param string $name Pillar or category name
     * @return string|null
     */
    public static function getSlug(string $name): ?string
    {
        $category = self::find($name);
        if ($category === null) {
            return null;
        }

        return $category['slug'];
    }

    /**
     * Return the db value for a given category name
     *
     * Returns null on no results
     *
     * @param string $name Pillar or category name
     * @return string|null
     */
    public static function getDbValue(string $name): ?string
    {
        $category = self::find($name);
        if ($category === null) {
            return null;
        }

        return $category['db_value'];
    }

    /**
     * Return the db value for a given category name
     *
     * Returns null on no results
     *
     * @param string $slug Pillar or category slug
     * @return string|null
     */
    public static function getDbValueBySlug(string $slug): ?string
    {
        $category = self::findBySlug($slug);
        if ($category === null) {
            return null;
        }

        return $category['db_value'];
    }
}
