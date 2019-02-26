<?php
declare(strict_types=1);

namespace App\Utils;

class FrameworkCategories
{

    /**
     * Array of pillars and categories => url slugs
     * @var array
     */
    protected static $categories = [
        'Buildings' => [
            'Construction'          => 'construction',
            'Utilities & Fuels'     => 'utilities-fuels',
            'Workplace'             => 'workplace',
        ],
        'Corporate Services' => [
            'Contact Centres'       => 'contact-centres',
            'Document Management & Logistics' => 'document-management-logistics',
            'Financial Services'    => 'financial-services',
            'Fleet'                 => 'fleet',
            'Marcomms & Research'   => 'marcomms-research',
            'Travel'                => 'travel',
        ],
        'People' => [
            'People Services'       => 'people-services',
            'Professional Services' => 'professional-services',
            'Workforce'             => 'workforce',
        ],
        'Technology' => [
            'Digital Future'        => 'digital-future',
            'Network Services'      => 'network-services',
            'Software'              => 'software',
            'Technology Products & Services' => 'technology-products-services',
        ],
    ];

    /**
     * Return A-Z array of all categories (category name => category URL slug)
     *
     * @param string $pillar
     * @return array
     */
    protected static function getAll(): array
    {
        $data = [];
        foreach (self::$categories as $pillar => $items) {
            foreach ($items as $name => $slug) {
                $data[$name] = $slug;
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
        $categories = self::getAll();
        if (array_key_exists($name, $categories)) {
            return $categories[$name];
        }

        return null;
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
        if (isset(self::$categories[$pillar])) {
            return self::$categories[$pillar];
        }
        return [];
    }

}
