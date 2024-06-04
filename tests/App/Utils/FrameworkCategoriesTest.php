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
        $cat = FrameworkCategories::find('Corporate');
        $this->assertEquals(5, count($cat['categories']));
        $this->assertTrue(isset($cat['categories']));
        
        $cat = FrameworkCategories::find('Estates');
        $this->assertEquals(4, count($cat['categories']));
        $this->assertTrue(isset($cat['categories']));
        
        $cat = FrameworkCategories::find('Technology');
        $this->assertEquals(5, count($cat['categories']));
        $this->assertTrue(isset($cat['categories']));

        $cat = FrameworkCategories::find('HR and Workforce Services');
        $this->assertEquals('hr-and-workforce-services', $cat['slug']);
        $this->assertFalse(isset($cat['categories']));

        $cat = FrameworkCategories::find('Estates Support Services');
        $this->assertEquals('estates-support-services', $cat['slug']);
        $this->assertFalse(isset($cat['categories']));

        $cat = FrameworkCategories::find('Cloud and Hosting');
        $this->assertEquals('cloud-and-hosting', $cat['slug']);
        $this->assertFalse(isset($cat['categories']));

        $cat = FrameworkCategories::find('FakeCat');
        $this->assertNull($cat);
    }

    public function testCategories()
    {
        $categories = FrameworkCategories::getAll();
        $this->assertEquals(14, count($categories));
        $first = current($categories);
        $this->assertEquals('cloud-and-hosting', $first);
        $last = end($categories);
        $this->assertEquals('travel-accommodation-and-Venues', $last);

        $this->assertEquals('energy', FrameworkCategories::getSlug('Energy'));
        $this->assertEquals('professional-services', FrameworkCategories::getSlug('Professional Services'));
        $this->assertEquals('digital-capability-and-delivery', FrameworkCategories::getSlug('Digital Capability and Delivery'));
        $this->assertEquals('Estates Support Services', FrameworkCategories::getNameBySlug('estates-support-services'));
        $this->assertEquals('Facilities Management', FrameworkCategories::getNameBySlug('facilities-management'));
        $this->assertEquals('Technology Services', FrameworkCategories::getNameBySlug('technology-services'));
        $this->assertEquals(4, count(FrameworkCategories::getAllByPillar('Estates')));
    }

    public function testDbValue()
    {
        $this->assertEquals('Corporate', FrameworkCategories::getDbValue('Corporate'));
        $this->assertEquals('Travel, Accommodation and Venues', FrameworkCategories::getDbValue('Travel, Accommodation and Venues'));
        $this->assertEquals('Facilities Management', FrameworkCategories::getDbValue('Facilities Management'));
        $this->assertEquals('Technology', FrameworkCategories::getDbValue('Technology'));
    }

    public function testRedirectFromUtilitiesFuelsToEnergy()
    {
        $client = static::createClient();
        $client->request('GET', '/agreements/category/utilities-fuels');
        $response = $client->getResponse();

        $this->assertEquals(301, $response->getStatusCode());

        $this->assertResponseRedirects('/agreements/category/energy');
    }

    public function testRedirectFromTravelToTravelTransportAccommodationAndVenuesCategory()
    {
        $client = static::createClient();
        $client->request('GET', '/agreements/category/travel');
        $response = $client->getResponse();

        $this->assertEquals(301, $response->getStatusCode());

        $this->assertResponseRedirects('/agreements/category/travel-transport-accommodation-and-venues');
    }

    public function testRedirectFromDigitalFuturesToDigitalSpecialists()
    {
        $client = static::createClient();
        $client->request('GET', '/agreements/category/digital-future');
        $response = $client->getResponse();

        $this->assertEquals(301, $response->getStatusCode());

        $this->assertResponseRedirects('/agreements/category/digital-specialists');
    }

}
