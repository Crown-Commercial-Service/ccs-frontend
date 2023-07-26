<?php

declare(strict_types=1);

namespace App\Tests\App\Utils;

use App\Utils\FrameworkCategories;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FrameworkCategoriesTest extends WebTestCase
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

        $cat = FrameworkCategories::find('Below Threshold');
        $this->assertEquals('below-threshold', $cat['slug']);
        $this->assertFalse(isset($cat['categories']));

        $cat = FrameworkCategories::find('Fake');
        $this->assertNull($cat);
    }

    public function testCategories()
    {
        $categories = FrameworkCategories::getAll();
        $this->assertEquals(20, count($categories));
        $first = current($categories);
        $this->assertEquals('below-threshold', $first);
        $last = end($categories);
        $this->assertEquals('workplace', $last);

        $this->assertEquals('energy', FrameworkCategories::getSlug('Energy'));
        $this->assertEquals('estates-decarbonisation', FrameworkCategories::getSlug('Estates Decarbonisation'));
        $this->assertEquals('document-management-logistics', FrameworkCategories::getSlug('Document Management & Logistics'));
        $this->assertEquals('Technology Products & Services', FrameworkCategories::getNameBySlug('technology-products-services'));
        $this->assertEquals('Marcomms & Research', FrameworkCategories::getNameBySlug('marcomms-research'));
        $this->assertEquals('Below Threshold', FrameworkCategories::getNameBySlug('below-threshold'));
        $this->assertEquals(5, count(FrameworkCategories::getAllByPillar('People')));
    }

    public function testDbValue()
    {
        $this->assertEquals('Buildings', FrameworkCategories::getDbValue('Buildings'));
        $this->assertEquals('People', FrameworkCategories::getDbValue('People'));
        $this->assertEquals('Marcomms & Research', FrameworkCategories::getDbValue('Marcomms & Research'));
        $this->assertEquals('Below Threshold', FrameworkCategories::getDbValue('Below Threshold'));
    }

    public function testRedirectFromUtilitiesFuelsToEnergy()
    {
        $client = static::createClient();
        $client->request('GET', '/agreements/category/utilities-fuels');
        $response = $client->getResponse();

        $this->assertEquals(302, $response->getStatusCode());

        $this->assertResponseRedirects('/agreements/category/energy');
    }

    public function testRedirectFromTravelToTravelTransportAccommodationAndVenuesCategory()
    {
        $client = static::createClient();
        $client->request('GET', '/agreements/category/travel');
        $response = $client->getResponse();

        $this->assertEquals(302, $response->getStatusCode());

        $this->assertResponseRedirects('/agreements/category/travel-transport-accommodation-and-venues');
    }

    public function testRedirectFromDigitalFuturesToDigitalSpecialists()
    {
        $client = static::createClient();
        $client->request('GET', '/agreements/category/digital-future');
        $response = $client->getResponse();

        $this->assertEquals(302, $response->getStatusCode());

        $this->assertResponseRedirects('/agreements/category/digital-specialists');
    }

    public function testRedirectFromNetworkServicesToNetworkSolutions()
    {
        $client = static::createClient();
        $client->request('GET', '/agreements/category/network-services');
        $response = $client->getResponse();

        $this->assertEquals(302, $response->getStatusCode());

        $this->assertResponseRedirects('/agreements/category/network-solutions');
    }
}
