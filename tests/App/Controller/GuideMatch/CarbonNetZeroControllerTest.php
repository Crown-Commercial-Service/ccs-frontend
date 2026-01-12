<?php

namespace App\Tests\Controller\GuideMatch;

use App\Controller\GuideMatch\CarbonNetZeroController;
use App\Service\GuideJourneyService;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use ReflectionClass;

class CarbonNetZeroControllerTest extends TestCase
{
    private $controller;

    protected function setUp(): void
    {
        // We only need to mock the constructor dependencies to instantiate the class
        $journeyService = $this->createMock(GuideJourneyService::class);
        $cache = $this->createMock(CacheItemPoolInterface::class);

        $this->controller = new CarbonNetZeroController($journeyService, $cache);
    }

    public function testJourneyNameIsSetCorrectly()
    {
        // Access the protected property $journeyName using Reflection
        $reflection = new ReflectionClass($this->controller);
        $property = $reflection->getProperty('journeyName');
        $property->setAccessible(true);

        $this->assertEquals('cnz', $property->getValue($this->controller));
    }

    public function testGetLandingPageDataReturnsCorrectContent()
    {
        // Access the protected method getLandingPageData using Reflection
        $reflection = new ReflectionClass($this->controller);
        $method = $reflection->getMethod('getLandingPageData');
        $method->setAccessible(true);

        $data = $method->invoke($this->controller);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('title', $data);
        $this->assertArrayHasKey('description', $data);
        
        // specific text assertions
        $this->assertEquals('Find a carbon net zero solution', $data['title']);
        $this->assertStringContainsString('meet your carbon net zero goals', $data['description']);
    }

    public function testControllerConfiguration()
    {
        // 1. Instantiate the controller with mocks
        $journeyService = $this->createMock(GuideJourneyService::class);
        $cache = $this->createMock(CacheItemPoolInterface::class);
        
        $controller = new CarbonNetZeroController($journeyService, $cache);

        // 2. Check strict properties using Reflection (since they are protected)
        $reflection = new ReflectionClass($controller);
        
        // Verify Journey Name
        $journeyNameProp = $reflection->getProperty('journeyName');
        $journeyNameProp->setAccessible(true);
        $this->assertEquals('cnz', $journeyNameProp->getValue($controller));

        // Verify Landing Page Data
        $method = $reflection->getMethod('getLandingPageData');
        $method->setAccessible(true);
        $data = $method->invoke($controller);

        $this->assertEquals('Find a carbon net zero solution', $data['title']);
        $this->assertArrayHasKey('description', $data);
    }
}
