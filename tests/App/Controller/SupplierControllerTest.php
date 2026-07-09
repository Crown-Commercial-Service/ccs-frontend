<?php

declare(strict_types=1);

namespace App\Tests\App\Controller;

class SupplierControllerTest extends AbstractControllerTestCase
{
    public function testListEndpointRendersSuccessfully(): void
    {
        $pagination = new \Strata\Frontend\Content\Pagination\Pagination();
        $pagination->setResultsPerPage(20);
        $pagination->setTotalResults(1);
        $pagination->setTotalPages(1);
        $pagination->setPage(1);

        $realCollection = new \Strata\Frontend\Content\PageCollection($pagination);
        $realCollection->getMetadata()->add('facets', [
            'frameworks' => [],
            'lots' => [],
        ]);

        $mockContentType = $this->createMock(\Strata\Frontend\ContentModel\ContentType::class);
        $mockContentModel = $this->createMock(\Strata\Frontend\ContentModel\ContentModel::class);
        $mockContentModel->method('getContentType')->willReturn($mockContentType);

        $mockSearchApi = $this->createMock(\Strata\Frontend\Cms\RestData::class);
        $mockSearchApi->method('getContentModel')->willReturn($mockContentModel);
        $mockSearchApi->method('getContentType')->willReturn($mockContentType);
        $mockSearchApi->method('list')->willReturn($realCollection);

        $this->injectControllerMocks(searchApi: $mockSearchApi);

        $this->client->request('GET', '/suppliers/1');

        $this->assertResponseIsSuccessful();
        $html = $this->client->getResponse()->getContent();

        $this->assertStringContainsString('Search suppliers', $html);
    }

    public function testSearchEndpointRendersSuccessfullyWithSelectedFilters(): void
    {
        $pagination = new \Strata\Frontend\Content\Pagination\Pagination();
        $pagination->setResultsPerPage(20);
        $pagination->setTotalResults(1);
        $pagination->setTotalPages(1);
        $pagination->setPage(1);

        $realCollection = new \Strata\Frontend\Content\PageCollection($pagination);
        $realCollection->getMetadata()->add('facets', [
            'frameworks' => [
                ['rm_number' => 'RM1234', 'title' => 'Test Framework', 'doc_count' => 1],
            ],
            'lots' => [
                ['id' => '1', 'lot_number' => '1', 'title' => 'North Lot', 'doc_count' => 1],
            ],
        ]);

        $mockContentType = $this->createMock(\Strata\Frontend\ContentModel\ContentType::class);
        $mockContentModel = $this->createMock(\Strata\Frontend\ContentModel\ContentModel::class);
        $mockContentModel->method('getContentType')->willReturn($mockContentType);

        $mockSearchApi = $this->createMock(\Strata\Frontend\Cms\RestData::class);
        $mockSearchApi->method('getContentModel')->willReturn($mockContentModel);
        $mockSearchApi->method('getContentType')->willReturn($mockContentType);
        $mockSearchApi->method('list')->willReturn($realCollection);

        $this->injectControllerMocks(searchApi: $mockSearchApi);

        $this->client->request('GET', '/suppliers/search/1?q=acme&t=old&framework=RM1234&lot-filter-nested=1&limit=20');

        $this->assertResponseIsSuccessful();
        $html = $this->client->getResponse()->getContent();

        $this->assertStringContainsString('Search suppliers', $html);
        $this->assertStringContainsString('Test Framework', $html);
        $this->assertStringContainsString('North Lot', $html);
    }

    public function testShowEndpointRendersSupplierSuccessfully(): void
    {
        $mockContent = \App\Tests\App\Mock\CMSContentMockFactory::createMockContent([
            'name' => 'Acme Supplier',
            'slugArray' => ['acme-supplier'],
            'trading_names' => [],
            'live_frameworks' => [
                [
                    'title' => 'Example Framework',
                    'rm_number' => 'RM9999',
                    'status' => 'Live',
                    'lots' => [
                        [
                            'lot_number' => '1',
                            'title' => 'Lot One',
                            'status' => 'Live',
                            'supplier_website_contact' => ['value' => false],
                            'supplier_contact_name' => 'Jane Doe',
                            'supplier_contact_email' => 'jane@example.com',
                        ],
                    ],
                ],
            ],
            'phone_number' => '01234 567890',
        ]);

        $mockPage = $this->createMock(\Strata\Frontend\Content\Page::class);
        $mockPage->method('getContent')->willReturn($mockContent);

        $mockApi = $this->createMock(\Strata\Frontend\Cms\RestData::class);
        $mockApi->method('getOne')->willReturn($mockPage);

        $this->injectControllerMocks(api: $mockApi);

        $this->client->request('GET', '/suppliers/123/acme-supplier');

        $this->assertResponseIsSuccessful();
        $html = $this->client->getResponse()->getContent();

        $this->assertStringContainsString('Acme Supplier', $html);
        $this->assertStringContainsString('Example Framework', $html);
        $this->assertStringContainsString('Lot One', $html);
    }

    public function testShowEndpointReturnsNotFoundForInvalidSlug(): void
    {
        $mockContent = \App\Tests\App\Mock\CMSContentMockFactory::createMockContent([
            'name' => 'Acme Supplier',
            'slugArray' => ['acme-supplier'],
            'trading_names' => [],
            'live_frameworks' => [],
        ]);

        $mockPage = $this->createMock(\Strata\Frontend\Content\Page::class);
        $mockPage->method('getContent')->willReturn($mockContent);

        $mockApi = $this->createMock(\Strata\Frontend\Cms\RestData::class);
        $mockApi->method('getOne')->willReturn($mockPage);

        $this->injectControllerMocks(api: $mockApi);

        $this->client->request('GET', '/suppliers/123/wrong-slug');

        $this->assertResponseStatusCodeSame(404);
    }

    private function injectControllerMocks(?\Strata\Frontend\Cms\RestData $api = null, ?\Strata\Frontend\Cms\RestData $searchApi = null): void
    {
        $controller = static::getContainer()->get(\App\Controller\SuppliersController::class);
        $reflection = new \ReflectionClass($controller);

        if ($api !== null) {
            $apiProperty = $reflection->getProperty('api');
            $apiProperty->setAccessible(true);
            $apiProperty->setValue($controller, $api);
        }

        if ($searchApi !== null) {
            $searchApiProperty = $reflection->getProperty('searchApi');
            $searchApiProperty->setAccessible(true);
            $searchApiProperty->setValue($controller, $searchApi);
        }
    }
}
