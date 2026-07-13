<?php

declare(strict_types=1);

namespace App\Tests\App\Controller;

use App\Controller\ApiController;
use App\Exception\ApiException;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Request;

class ApiControllerTest extends TestCase
{
    private function createController(MockHttpClient $client, string $cmsBaseUrl = 'http://fake-cms.local', array $pardotUrls = []): ApiController
    {
        return new ApiController($client, $cmsBaseUrl, $pardotUrls);
    }

    private function createCache(): CacheItemPoolInterface
    {
        return $this->createMock(CacheItemPoolInterface::class);
    }

    public function testGetCmsUrlThrowsExceptionWhenBaseUrlIsEmpty(): void
    {
        $controller = $this->createController(new MockHttpClient(), '');

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Cannot determine CMS API URL');

        $controller->getCmsUrl('/search-api/suppliers');
    }

    public function testGetCmsUrlAppendsPathAndTrimsTrailingSlash(): void
    {
        $controller = $this->createController(new MockHttpClient(), 'http://fake-cms.local/');

        $this->assertSame('http://fake-cms.local/search-api/suppliers', $controller->getCmsUrl('/search-api/suppliers'));
    }

    public function testSuppliersReturnsJsonResponseOnSuccess(): void
    {
        $client = new MockHttpClient(new MockResponse(json_encode(['results' => []])));
        $controller = $this->createController($client);

        $response = $controller->suppliers(Request::create('/api/suppliers', 'GET', ['keyword' => 'acme']), $this->createCache());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString('{"results":[]}', (string) $response->getContent());
    }

    public function testSuppliersThrowsApiExceptionOnNonSuccessStatus(): void
    {
        $client = new MockHttpClient(new MockResponse('Internal error', ['http_code' => 500]));
        $controller = $this->createController($client);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessageMatches('/Error with Search Suppliers API query/');

        $controller->suppliers(Request::create('/api/suppliers', 'GET'), $this->createCache());
    }

    public function testFrameworksReturnsJsonResponseOnSuccess(): void
    {
        $client = new MockHttpClient(new MockResponse(json_encode(['results' => ['RM1234']])));
        $controller = $this->createController($client);

        $response = $controller->frameworks(Request::create('/api/frameworks', 'GET', ['status' => 'Live']), $this->createCache());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString('{"results":["RM1234"]}', (string) $response->getContent());
    }

    public function testFrameworksThrowsApiExceptionOnNonSuccessStatus(): void
    {
        $client = new MockHttpClient(new MockResponse('Internal error', ['http_code' => 503]));
        $controller = $this->createController($client);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessageMatches('/Error with Search Framework API query/');

        $controller->frameworks(Request::create('/api/frameworks', 'GET'), $this->createCache());
    }

    public function testNewsReturnsJsonResponseWithPaginationHeaders(): void
    {
        $client = new MockHttpClient(new MockResponse(json_encode(['id' => 1]), [
            'response_headers' => ['x-wp-totalpages' => '3', 'x-wp-total' => '25'],
        ]));
        $controller = $this->createController($client);

        $response = $controller->news(Request::create('/api/news', 'GET', ['page' => '1']), $this->createCache());

        $payload = json_decode((string) $response->getContent(), true);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(3, $payload['meta']['X-WP-TotalPages']);
        $this->assertSame(25, $payload['meta']['X-WP-Total']);
    }

    public function testNewsThrowsApiExceptionOnNonSuccessStatus(): void
    {
        $client = new MockHttpClient(new MockResponse('Internal error', ['http_code' => 500]));
        $controller = $this->createController($client);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessageMatches('/Error with news filter API query/');

        $controller->news(Request::create('/api/news', 'GET'), $this->createCache());
    }

    public function testEventsReturnsJsonResponseWithPaginationHeaders(): void
    {
        $client = new MockHttpClient(new MockResponse(json_encode(['id' => 1]), [
            'response_headers' => ['x-wp-totalpages' => '2', 'x-wp-total' => '10'],
        ]));
        $controller = $this->createController($client);

        $response = $controller->events(Request::create('/api/events', 'GET'), $this->createCache());

        $payload = json_decode((string) $response->getContent(), true);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(2, $payload['meta']['X-WP-TotalPages']);
        $this->assertSame(10, $payload['meta']['X-WP-Total']);
    }

    public function testEventsThrowsApiExceptionOnNonSuccessStatus(): void
    {
        $client = new MockHttpClient(new MockResponse('Internal error', ['http_code' => 500]));
        $controller = $this->createController($client);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessageMatches('/Error with event filter API query/');

        $controller->events(Request::create('/api/events', 'GET'), $this->createCache());
    }

    public function testPardotEmailRejectsNonXmlHttpRequest(): void
    {
        $controller = $this->createController(new MockHttpClient());

        $request = Request::create('/api/pardot-email', 'POST', [], [], [], [], json_encode(['email' => 'a@b.com', 'subject' => 'contact']));

        $response = $controller->pardotEmail($request);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('You can only send AJAX', (string) $response->getContent());
    }

    private function createXhrRequest(array $body): Request
    {
        $request = Request::create('/api/pardot-email', 'POST', [], [], [], [], json_encode($body));
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        return $request;
    }

    public function testPardotEmailRejectsMissingEmail(): void
    {
        $controller = $this->createController(new MockHttpClient());

        $response = $controller->pardotEmail($this->createXhrRequest(['subject' => 'contact']));

        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('You must pass an email variable', (string) $response->getContent());
    }

    public function testPardotEmailRejectsInvalidEmail(): void
    {
        $controller = $this->createController(new MockHttpClient());

        $response = $controller->pardotEmail($this->createXhrRequest(['email' => 'not-an-email', 'subject' => 'contact']));

        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('An invalid email has been passed', (string) $response->getContent());
    }

    public function testPardotEmailRejectsMissingSubject(): void
    {
        $controller = $this->createController(new MockHttpClient());

        $response = $controller->pardotEmail($this->createXhrRequest(['email' => 'a@b.com']));

        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('You must pass a subject variable', (string) $response->getContent());
    }

    public function testPardotEmailRejectsWhenNoPardotUrlConfiguredForSubject(): void
    {
        $controller = $this->createController(new MockHttpClient(), 'http://fake-cms.local', ['contact' => '']);

        $response = $controller->pardotEmail($this->createXhrRequest(['email' => 'a@b.com', 'subject' => 'unknown-campaign']));

        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Please set PARDOT_EMAIL_FORM_HANDLER_URL', (string) $response->getContent());
    }

    public function testSetPardotFormUrlMatchesConfiguredCampaignCode(): void
    {
        $controller = $this->createController(new MockHttpClient(), 'http://fake-cms.local', [
            'contact' => 'https://pardot.example.com/contact-form',
            'newsletter' => 'https://pardot.example.com/newsletter-form',
        ]);

        $this->assertSame('https://pardot.example.com/contact-form', $controller->setPardotFormURL('Website - Contact Form'));
        $this->assertSame('https://pardot.example.com/newsletter-form', $controller->setPardotFormURL('Website - Newsletter'));
    }

    public function testSetPardotFormUrlReturnsNullWhenNoCodeMatches(): void
    {
        $controller = $this->createController(new MockHttpClient(), 'http://fake-cms.local', [
            'contact' => 'https://pardot.example.com/contact-form',
        ]);

        $this->assertNull($controller->setPardotFormURL('Website - Something Else'));
    }
}
