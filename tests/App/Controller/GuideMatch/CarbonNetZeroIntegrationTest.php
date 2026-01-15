<?php

namespace App\Tests\Controller\GuideMatch;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Strata\Frontend\Cms\RestData;
use Strata\Frontend\Exception\NotFoundException;
use Strata\Frontend\Cms\Wordpress;
use App\Service\GuideJourneyService;

class CarbonNetZeroIntegrationTest extends WebTestCase
{
    public static function setUpBeforeClass(): void
    {
        // Force API variables into the environment to prevent startup crashes
        putenv('APP_API_BASE_URL=https://example.com/');
        putenv('WORDPRESS_API_URL=https://example.com');
        // Ensure REDIRECT_API_URL is set so the service container can build the object
        putenv('REDIRECT_API_URL=https://example.com/redirects');

        parent::setUpBeforeClass();
    }

    public function testLandingPageLoads()
    {
        $client = static::createClient();
        $this->injectMocks();

        $client->request('GET', '/carbon-net-zero');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Find a carbon net zero solution');
    }

    public function testInvalidRouteReturns404()
    {
        $client = static::createClient();
        $this->injectMocks(); // <--- AND call it here too!

        $client->request('GET', '/carbon-net-zero/garbage-path');

        $this->assertResponseStatusCodeSame(404);
    }

    /**
     * Helper to inject mocks into the container for the current client.
     */
    private function injectMocks()
    {
        // 1. Mock Redirection API
        $mockRedirection = $this->createMock(RestData::class);
        $mockRedirection->method('getOne')->willThrowException(new NotFoundException());
        static::getContainer()->set('app.api.redirection', $mockRedirection);

        // 2. Mock Wordpress API (Stub)
        $mockWordpress = $this->createStub(Wordpress::class);
        static::getContainer()->set(Wordpress::class, $mockWordpress);

        // 3. FIX: Mock the Journey Service (Stub)
        // This prevents the "example.comccs" error entirely because the real code never runs.
        $mockJourneyService = $this->createMock(GuideJourneyService::class);
        // Tell it to return an array instead of null
        $mockJourneyService->method('getJourneyData')->willReturn([
            'start_uuid' => 'test-start-node-uuid'
        ]);
        // Inject the mock into the container
        static::getContainer()->set(GuideJourneyService::class, $mockJourneyService);
    }
}
