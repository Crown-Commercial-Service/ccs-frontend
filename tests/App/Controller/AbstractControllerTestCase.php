<?php

declare(strict_types=1);

namespace App\Tests\App\Controller;

use App\Helper\ControllerHelper;
use Strata\Frontend\Cms\RestData;
use Strata\Frontend\Cms\Wordpress;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpClient\MockHttpClient;

abstract class AbstractControllerTestCase extends WebTestCase
{
    protected $client;
    protected $mockWordpressApi;
    protected $mockRestDataApi;
    protected $mockControllerHelper;
    protected $mockHttpClient;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Boot the Symfony kernel and browser client
        $this->client = static::createClient();

        // 1. Mock the main WordPress API
        $this->mockWordpressApi = $this->createMock(Wordpress::class);
        static::getContainer()->set(Wordpress::class, $this->mockWordpressApi);

        // 2. Mock the RestData API (used for Redirections, Glossary, etc.)
        $this->mockRestDataApi = $this->createMock(RestData::class);
        static::getContainer()->set(RestData::class, $this->mockRestDataApi);

        // 3. Mock the ControllerHelper (intercepts getHomeMessageBanner, getCSCMessage, etc.)
        $this->mockControllerHelper = $this->createMock(ControllerHelper::class);
        
        // Set safe default return values so tests don't crash if they forget to mock them
        $this->mockControllerHelper->method('getHomeMessageBanner')->willReturn(null);
        $this->mockControllerHelper->method('getCSCMessage')->willReturn('');
        $this->mockControllerHelper->method('getOrgId')->willReturn('test_org_id');
        
        static::getContainer()->set(ControllerHelper::class, $this->mockControllerHelper);

        // 4. Mock the Symfony HTTP Client (Intercepts Salesforce, Pardot, and API calls)
        $this->mockHttpClient = new MockHttpClient();
        $traceableHttpClient = new \Symfony\Component\HttpClient\TraceableHttpClient($this->mockHttpClient);
        static::getContainer()->set(HttpClientInterface::class, $traceableHttpClient);
    }

    public function createPageFromJson(array $pageData): \Strata\Frontend\Content\Page
    {
        // Outsource the complex dynamic content collection wrapping to our factory
        $mockContent = \App\Tests\App\Mock\CMSContentMockFactory::createMockContent($pageData['acf'] ?? []);

        $mockPage = $this->createMock(\Strata\Frontend\Content\Page::class);
        
        $mockPage->method('getTitle')->willReturn($pageData['title']['rendered'] ?? 'Fallback Title');
        $mockPage->method('getTemplate')->willReturn($pageData['template'] ?? '');
        $mockPage->method('getContent')->willReturn($mockContent);

        return $mockPage;
    }
}