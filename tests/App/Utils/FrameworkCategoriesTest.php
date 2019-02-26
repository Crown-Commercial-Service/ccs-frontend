<?php
declare(strict_types=1);

namespace App\Tests\App\Utils;

use App\Utils\FrameworkCategories;
use PHPUnit\Framework\TestCase;

class FrameworkCategoriesTest extends TestCase
{
    public function testCategories()
    {
        $categories = FrameworkCategories::getCategories();
        $this->assertEquals(16, count($categories));
        $first = current($categories);
        $this->assertEquals('construction', $first);
        $last = end($categories);
        $this->assertEquals('workplace', $last);

        $this->assertEquals('utilities-fuels', FrameworkCategories::getSlug('Utilities & Fuels'));
        $this->assertEquals('document-management-logistics', FrameworkCategories::getSlug('Document Management & Logistics'));
        $this->assertEquals('Technology Products & Services', FrameworkCategories::getName( 'technology-products-services'));
        $this->assertEquals('Marcomms & Research', FrameworkCategories::getName('marcomms-research'));
        $this->assertEquals(3, count(FrameworkCategories::getCategoriesByPillar('People')));
    }
}