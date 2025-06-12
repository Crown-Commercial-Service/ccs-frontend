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
        $this->assertEquals(6, count($cat['categories']));
        $this->assertTrue(isset($cat['categories']));

        $cat = FrameworkCategories::find('Estates');
        $this->assertEquals(3, count($cat['categories']));
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

        $cat = FrameworkCategories::find('Outsourced Services');
        $this->assertEquals('outsourced-services', $cat['slug']);
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
        $this->assertEquals('Facilities Management', FrameworkCategories::getNameBySlug('facilities-management'));
        $this->assertEquals('Digital and technology Services', FrameworkCategories::getNameBySlug('digital-and-technology-services'));
        $this->assertEquals(3, count(FrameworkCategories::getAllByPillar('Estates')));
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

        $this->assertEquals(302, $response->getStatusCode());

        $this->assertResponseRedirects('/agreements?category%5B0%5D=Energy');
    }

    public function testRedirectFromTravelToTravelAccommodationAndVenuesCategory()
    {
        $client = static::createClient();
        $client->request('GET', '/agreements/category/travel');
        $response = $client->getResponse();

        $this->assertEquals(302, $response->getStatusCode());

        $this->assertResponseRedirects('/agreements?category%5B0%5D=Travel,%20Accommodation%20and%20Venues');
    }

    public function testRedirectFromDigitalFuturesToDigitalCapabilityAndDelivery()
    {
        $client = static::createClient();
        $client->request('GET', '/agreements/category/digital-future');
        $response = $client->getResponse();

        $this->assertEquals(302, $response->getStatusCode());

        $this->assertResponseRedirects('/agreements?category%5B0%5D=Digital%20Capability%20and%20Delivery');
    }

    public function testRedirectFromNetworkSolutionsToNetworkServices()
    {
        $client = static::createClient();
        $client->request('GET', '/agreements/category/network-solutions');
        $response = $client->getResponse();

        $this->assertEquals(302, $response->getStatusCode());

        $this->assertResponseRedirects('/agreements?category%5B0%5D=Network%20Services');
    }

    public function testRedirectFromWorkplaceToEstates()
    {
        $client = static::createClient();
        $client->request('GET', '/agreements/category/workplace');
        $response = $client->getResponse();

        $this->assertEquals(302, $response->getStatusCode());

        $this->assertResponseRedirects('/agreements?pillar%5B0%5D=Estates');
    }

    public function testRedirectFromtechnologyProductsServicesToTechnology()
    {
        $client = static::createClient();
        $client->request('GET', '/agreements/category/technology-products-services');
        $response = $client->getResponse();

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertResponseRedirects('/agreements?pillar%5B0%5D=Technology');
    }
    public function testRedirectFromWorkforceHealthEducationToHrAndWorkforceServices()
    {
        $client = static::createClient();
        $client->request('GET', '/agreements/category/workforce-health-education');
        $response = $client->getResponse();

        $this->assertEquals(302, $response->getStatusCode());

        $this->assertResponseRedirects('/agreements?category%5B0%5D=HR%20and%20Workforce%20Services');
    }

    public function testRedirectFromEstateSupportServicesToFacilitiesManagement()
    {
        $client = static::createClient();
        $client->request('GET', '/agreements/category/estate-support-services');
        $response = $client->getResponse();

        $this->assertEquals(302, $response->getStatusCode());

        $this->assertResponseRedirects('/agreements?category%5B0%5D=Facilities%20Management');
    }

    public function testRedirectFromTechnologyServicesToDigitalTechnologyServices()
    {
        $client = static::createClient();
        $client->request('GET', '/agreements/category/technology-services');
        $response = $client->getResponse();

        $this->assertEquals(302, $response->getStatusCode());

        $this->assertResponseRedirects('/agreements?category%5B0%5D=Digital%20and%20Technology%20Services');
    }

    public function testRedirectFromDigitalCapabilityToDigitalTechnologyServices()
    {
        $client = static::createClient();
        $client->request('GET', '/agreements/category/digital-capability-and-delivery');
        $response = $client->getResponse();

        $this->assertEquals(302, $response->getStatusCode());

        $this->assertResponseRedirects('/agreements?category%5B0%5D=Digital%20and%20Technology%20Services');
    }

    public function testRedirectFromSoftwareAndHardwareToSoftware()
    {
        $client = static::createClient();
        $client->request('GET', '/agreements/category/software-and-hardware');
        $response = $client->getResponse();

        $this->assertEquals(302, $response->getStatusCode());

        $this->assertResponseRedirects('/agreements?category%5B0%5D=Software');
    }
}
