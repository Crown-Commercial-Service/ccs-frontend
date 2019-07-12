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
        $this->assertEquals(4, count($cat));
        $this->assertTrue(isset($cat['categories']));

        $cat = FrameworkCategories::find('Marcomms & Research');
        $this->assertEquals('marcomms-research', $cat['slug']);
        $this->assertFalse(isset($cat['categories']));

        $cat = FrameworkCategories::find('Workplace');
        $this->assertEquals('workplace', $cat['slug']);
        $this->assertFalse(isset($cat['categories']));

        $cat = FrameworkCategories::find('Fake');
        $this->assertNull($cat);
    }

    public function testCategories()
    {
        $categories = FrameworkCategories::getAll();
        $this->assertEquals(17, count($categories));
        $first = current($categories);
        $this->assertEquals('construction', $first);
        $last = end($categories);
        $this->assertEquals('workplace', $last);

        $this->assertEquals('utilities-fuels', FrameworkCategories::getSlug('Utilities & Fuels'));
        $this->assertEquals('document-management-logistics', FrameworkCategories::getSlug('Document Management & Logistics'));
        $this->assertEquals('Technology Products & Services', FrameworkCategories::getNameBySlug('technology-products-services'));
        $this->assertEquals('Marcomms & Research', FrameworkCategories::getNameBySlug('marcomms-research'));
        $this->assertEquals(3, count(FrameworkCategories::getAllByPillar('People')));
    }

    public function testDbValue()
    {
        $this->assertEquals('Buildings', FrameworkCategories::getDbValue('Buildings'));
        $this->assertEquals('People', FrameworkCategories::getDbValue('People'));
        $this->assertEquals('Marcomms & Research', FrameworkCategories::getDbValue('Marcomms & Research'));
    }
}
