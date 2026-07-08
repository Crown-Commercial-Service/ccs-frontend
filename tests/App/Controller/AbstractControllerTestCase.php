<?php

declare(strict_types=1);

namespace App\Tests\App\Controller;

use App\Helper\ControllerHelper;
use App\Tests\App\Mock\MockControllerHelper;
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

        // 3. Use a concrete test helper stub so instance and static-style helper calls work reliably.
        $this->mockControllerHelper = new MockControllerHelper();
        $this->mockControllerHelper->setHomeMessageBanner(null);
        $this->mockControllerHelper->setCscMessage('');
        $this->mockControllerHelper->setOrgId('test_org_id');

        static::getContainer()->set(ControllerHelper::class, $this->mockControllerHelper);

        // 4. Mock the Symfony HTTP Client (Intercepts Salesforce, Pardot, and API calls)
        $this->mockHttpClient = new MockHttpClient();
        $traceableHttpClient = new \Symfony\Component\HttpClient\TraceableHttpClient($this->mockHttpClient);
        static::getContainer()->set(HttpClientInterface::class, $traceableHttpClient);
    }

    public function createPageFromJson(array $pageData): \Strata\Frontend\Content\Page
    {
        $mockContent = \App\Tests\App\Mock\CMSContentMockFactory::createMockContent($pageData['acf'] ?? []);

        return new class($pageData, $mockContent) extends \Strata\Frontend\Content\Page {
            private array $pageData;
            protected $content;

            public function __construct(array $pageData, $content)
            {
                parent::__construct();
                $this->pageData = $pageData;
                $this->content = $content;
            }

            public function getTitle(): string
            {
                return $this->pageData['title']['rendered'] ?? 'Fallback Title';
            }

            public function getTemplate(): string
            {
                return $this->pageData['template'] ?? '';
            }

            public function getContent(): \Strata\Frontend\Content\Field\ContentFieldCollection
            {
                return $this->content;
            }

            public function getUrlSlug(): string
            {
                return $this->pageData['slug'] ?? '';
            }

            public function __get(string $name)
            {
                if ($name === 'content') {
                    return $this->content;
                }

                if ($name === 'template') {
                    return $this->getTemplate();
                }

                if ($name === 'title') {
                    return $this->getTitle();
                }

                if ($name === 'urlSlug') {
                    return $this->getUrlSlug();
                }

                return null;
            }
        };
    }
}
