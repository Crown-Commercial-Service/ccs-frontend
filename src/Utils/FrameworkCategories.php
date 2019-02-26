<?php
declare(strict_types=1);

namespace App\Utils;

use Symfony\Component\Yaml\Yaml;

class FrameworkCategories
{
    /**
     * Load categories config file
     */
    public static function loadConfig()
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
    protected static function getAll(): array
    {
        $data = [];
        $categories = self::loadConfig();
        foreach ($categories as $pillar) {
            foreach ($pillar['children'] as $name => $values) {
                $data[$name] = $values['slug'];
            }
        }
        ksort($data);

        return $data;
    }

    /**
     * Return the category name for a given category slug
     *
     * Returns null on no results
     *
     * @param string $slug
     * @return string|null
     */
    public static function getName(string $slug): ?string
    {
        $categories = self::getAll();
        foreach ($categories as $key => $val) {
            if ($slug === $val) {
                return $key;
            }
        }

        return null;
    }

    /**
     * Return the category slug for a given category name
     *
     * Returns null on no results
     *
     * @param string $name
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
     * Match either a pillar or category and return its data
     *
     * @param string $name
     * @return array|null
     */
    public static function find(string $name): ?array
    {
        $categories = self::loadConfig();
        foreach ($categories as $pillarName => $pillar) {
            if ($pillarName === $name) {
                return $pillar;
            }

            foreach ($pillar['children'] as $categoryName => $category) {
                if ($categoryName === $name) {
                    return $category;
                }
            }
        }
        return null;
    }

    /**
     * Return the db value for a given category name
     *
     * Returns null on no results
     *
     * @param string $name
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
     * Return A-Z array of all categories (category name => category URL slug)
     *
     * @return array
     */
    public static function getCategories(): array
    {
        return self::getAll();
    }

    /**
     * Return array of categories for a pillar (category name => category URL slug)
     *
     * Returns an empty array on no results
     *
     * @param string $pillar
     * @return array
     */
    public static function getCategoriesByPillar(string $pillar): array
    {
        $categories = self::loadConfig();
        $data = [];

        if (isset($categories[$pillar])) {
            foreach ($categories[$pillar]['children'] as $name => $values) {
                $data[$name] = $values['slug'];
            }
            return $data;
        }
        return [];
    }

}
