<?php
declare(strict_types=1);

namespace App\Tests\App\Utils;

use App\Utils\FrameworkCategories;
use PHPUnit\Framework\TestCase;

class FrameworkCategoriesTest extends TestCase
{

    public function testFind()
    {
        $cat = FrameworkCategories::find('People');
        $this->assertEquals(3, count($cat));
        $this->assertTrue(isset($cat['children']));

        $cat = FrameworkCategories::find('Marcomms & Research');
        $this->assertEquals('marcomms-research', $cat['slug']);
        $this->assertFalse(isset($cat['children']));

        $cat = FrameworkCategories::find('Workplace');
        $this->assertEquals('workplace', $cat['slug']);
        $this->assertFalse(isset($cat['children']));

        $cat = FrameworkCategories::find('Fake');
        $this->assertNull($cat);
    }

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

    public function testDbValue()
    {
        $this->assertEquals('Buildings', FrameworkCategories::getDbValue('Buildings'));
        $this->assertEquals('People', FrameworkCategories::getDbValue('People'));
        $this->assertEquals('Marcomms & Research', FrameworkCategories::getDbValue('Marcomms & Research'));
    }

}