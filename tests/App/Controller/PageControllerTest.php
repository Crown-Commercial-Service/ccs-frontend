<?php

declare(strict_types=1);

namespace App\Tests\App\Controller;

use App\Tests\App\Mock\CMSContentMockFactory;
use Symfony\Component\HttpClient\Response\MockResponse;

class PageControllerTest extends AbstractControllerTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Enforce strict browser-like error handling across the entire test suite
        set_error_handler(function ($severity, $message, $file, $line) {
            if ($severity === E_DEPRECATED || $severity === E_USER_DEPRECATED || str_contains($file, '/vendor/')) {
                return false; 
            }
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });
    }

    public function testHomepageLoadsWithMockedData(): void
    {
        $fixturePath = __DIR__ . '/../../Fixtures/homepage_components.json';
        
        // Feed the MockResponse into the inherited HttpClient
        $this->mockHttpClient->setResponseFactory([
            new MockResponse(file_get_contents($fixturePath), ['http_code' => 200])
        ]);

        // Mock complex Strata nested entities for News list
        $mockImage = $this->createMock(\Strata\Frontend\Content\Field\Image::class);
        $mockImage->method('byName')->willReturn('https://via.placeholder.com/150');

        $mockDate = $this->createMock(\Strata\Frontend\Content\Field\DateTime::class);
        $mockDate->method('format')->willReturn('23 June 2026'); 

        $mockPage = $this->createMock(\Strata\Frontend\Content\Page::class);
        $mockPage->method('getUrlSlug')->willReturn('fake-news-article');
        $mockPage->method('getTitle')->willReturn('Mocked News Article');
        $mockPage->method('getDateModified')->willReturn($mockDate); 
        $mockPage->method('getFeaturedImage')->willReturn($mockImage);
        $mockPage->method('getTaxonomies')->willReturn(['categories' => [['name' => 'Procurement']]]);

        $mockPagination = $this->createMock(\Strata\Frontend\Content\Pagination\PaginationInterface::class);
        $realCollection = new \Strata\Frontend\Content\PageCollection($mockPagination);
        $realCollection->addItem($mockPage);
        $realCollection->addItem($mockPage);

        // Tell the inherited WordPress API to return our collection
        $this->mockWordpressApi->method('listPages')->willReturn($realCollection);
        $this->mockWordpressApi->method('setContentType')->willReturnSelf();

        // Execute the request using the inherited client
        $this->client->request('GET', '/');

        // Assertions
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Transforming Public Procurement');
        $this->assertSelectorTextContains('body', 'How to supply');
        $this->assertSelectorTextContains('body', 'How to buy');
        $this->assertSelectorTextContains('body', 'Mocked News Article');
    }

    public function testGenericPageTemplateLoadsWithMockedData(): void
    {
        $pageFixturePath = __DIR__ . '/../../Fixtures/technology_page.json';
        $pageData = json_decode(file_get_contents($pageFixturePath), true);

        $cardsFixturePath = __DIR__ . '/../../Fixtures/option_cards.json';
        
        // Feed the Option Cards response to the inherited HTTP Client
        $this->mockHttpClient->setResponseFactory([
            new MockResponse(file_get_contents($cardsFixturePath), ['http_code' => 200])
        ]);
        $mockPage = $this->createPageFromJson($pageData);

        // Tell the inherited WordPress API to return our Factory page
        $this->mockWordpressApi->method('getPageByUrl')->willReturn($mockPage);
        $this->mockWordpressApi->method('setContentType')->willReturnSelf();

        // Execute
        $this->client->request('GET', '/products-and-services/technology');

        // Assertions
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.page-title', 'Technology');
        $this->assertSelectorTextContains('.ccs-inline-contact-form h2', 'Speak to an expert about technology agreements');
        $this->assertSelectorExists('input[name="subject"][value="TECH"]');
    }
}