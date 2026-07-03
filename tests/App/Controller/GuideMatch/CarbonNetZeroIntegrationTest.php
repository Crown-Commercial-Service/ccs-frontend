<?php

namespace App\Tests\Controller\GuideMatch;

use App\Tests\App\Controller\AbstractControllerTestCase;
use Strata\Frontend\Cms\RestData;
use Strata\Frontend\Exception\NotFoundException;
use Strata\Frontend\Cms\Wordpress;
use App\Service\GuideJourneyService;
use Symfony\Component\HttpClient\Response\MockResponse;

class CarbonNetZeroIntegrationTest extends AbstractControllerTestCase
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

    public function testInvalidRouteReturns404()
    {
       
        $this->mockHttpClient->setResponseFactory([
            new MockResponse('[]', ['http_code' => 200])
        ]);

        // Inject the feature-specific mocks 
        $this->injectMocks();

        $this->client->request('GET', '/carbon-net-zero/garbage-path');

        $this->assertResponseStatusCodeSame(404);
    }

    /**
     * Helper to inject mocks into the container for the current client.
     */
   private function injectMocks()
    {
        // 1. Mock Redirection API (Keep this string ID mapping as is)
        $mockRedirection = $this->createMock(RestData::class);
        $mockRedirection->method('getOne')->willThrowException(new NotFoundException());
        static::getContainer()->set('app.api.redirection', $mockRedirection);

        // 2.Remove the duplicate static::getContainer()->set(Wordpress::class) call!
        // The base class AbstractControllerTestCase already sets up and registers 
        // $this->mockWordpressApi cleanly in the container before the test begins.
        $this->mockWordpressApi->method('setContentType')->willReturnSelf();

        // 3. Mock the Journey Service (Stub)
        $mockJourneyService = $this->createMock(GuideJourneyService::class);
        $mockJourneyService->method('getJourneyData')->willReturn([
            'start_uuid' => 'test-start-node-uuid'
        ]);
        static::getContainer()->set(GuideJourneyService::class, $mockJourneyService);
    }
}