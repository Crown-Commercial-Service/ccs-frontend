<?php

declare(strict_types=1);

namespace App\Tests\App\Controller;

use App\Tests\App\Mock\CMSContentMockFactory;
use Strata\Frontend\Content\Page;
use Strata\Frontend\Content\PageCollection;
use Strata\Frontend\Content\Pagination\Pagination;
use Strata\Frontend\Content\Taxonomies\Term;
use Strata\Frontend\Content\Taxonomies\TermCollection;
use Strata\Frontend\Exception\NotFoundException;
use Strata\Frontend\Exception\PaginationException;
use Symfony\Component\HttpClient\Response\MockResponse;

class NewsControllerTest extends AbstractControllerTestCase
{
    public function testListEndpointRendersSuccessfully(): void
    {
        $this->stubAllTerms();

        $pagination = new Pagination();
        $pagination->setTotalResults(2);
        $pagination->setTotalPages(1);

        $realCollection = new PageCollection($pagination);
        $realCollection->addItem($this->createMockPage([
            'post_lead_text' => 'First article lead text',
        ], 'first-article'));
        $realCollection->addItem($this->createMockPage([
            'post_lead_text' => 'Second article lead text',
        ], 'second-article'));

        $this->mockWordpressApi->method('listPages')->willReturn($realCollection);

        $this->client->request('GET', '/news/1');

        $this->assertResponseIsSuccessful();
        $html = $this->client->getResponse()->getContent();

        $this->assertStringContainsString('First article lead text', $html);
        $this->assertStringContainsString('Second article lead text', $html);
    }

    public function testListEndpointThrowsNotFoundWhenListPagesThrowsNotFoundException(): void
    {
        $this->stubAllTerms();

        $this->mockWordpressApi->method('listPages')->willThrowException(new NotFoundException('not found'));

        $this->client->request('GET', '/news/1');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testListEndpointThrowsNotFoundWhenListPagesThrowsPaginationException(): void
    {
        $this->stubAllTerms();

        $this->mockWordpressApi->method('listPages')->willThrowException(new PaginationException('bad page'));

        $this->client->request('GET', '/news/1');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testListEndpointAppliesCategoryFilterFromQueryParams(): void
    {
        $this->stubAllTerms();

        $pagination = new Pagination();
        $pagination->setTotalResults(0);
        $pagination->setTotalPages(1);
        $emptyCollection = new PageCollection($pagination);

        $this->mockWordpressApi->expects($this->once())
            ->method('listPages')
            ->with(
                1,
                $this->callback(function (array $options) {
                    return $options['categories'] === '5' && $options['noPost'] === 0;
                })
            )
            ->willReturn($emptyCollection);

        $this->client->request('GET', '/news/1?categories[]=5');

        $this->assertResponseIsSuccessful();
    }

    public function testListEndpointUsesDefaultOptionsWhenNoFiltersProvided(): void
    {
        $this->stubAllTerms();

        $pagination = new Pagination();
        $pagination->setTotalResults(0);
        $pagination->setTotalPages(1);
        $emptyCollection = new PageCollection($pagination);

        $this->mockWordpressApi->expects($this->once())
            ->method('listPages')
            ->with(
                1,
                $this->callback(function (array $options) {
                    return $options === [
                        'whitepaper' => 1,
                        'webinar' => 1,
                        'per_page' => 5,
                        'digitalDownload' => '1,2',
                    ];
                })
            )
            ->willReturn($emptyCollection);

        $this->client->request('GET', '/news/1');

        $this->assertResponseIsSuccessful();
    }

    public function testListEndpointUsesPageFromRouteParameter(): void
    {
        $this->stubAllTerms();

        $pagination = new Pagination();
        $pagination->setTotalResults(0);
        $pagination->setTotalPages(1);
        $emptyCollection = new PageCollection($pagination);

        $this->mockWordpressApi->expects($this->once())
            ->method('listPages')
            ->with(3, $this->anything())
            ->willReturn($emptyCollection);

        $this->client->request('GET', '/news/3');

        $this->assertResponseIsSuccessful();
    }

    public function testShowEndpointRendersNewsArticleSuccessfully(): void
    {
        $mockPage = $this->createMockPage([
            'post_lead_text' => 'The article lead text',
            'content' => '<p>The article body</p>',
        ], 'my-article', 42);

        $this->mockWordpressApi->method('getPageByUrl')->willReturn($mockPage);

        $this->mockHttpClient->setResponseFactory(function () {
            return new MockResponse(json_encode([
                'acf' => [
                    'author_name_text' => 'Jane Author',
                    'author_image' => 'https://example.com/author.jpg',
                    'display_banner' => true,
                ],
            ]));
        });

        $this->client->request('GET', '/news/my-article');

        $this->assertResponseIsSuccessful();
        $html = $this->client->getResponse()->getContent();

        $this->assertStringContainsString('The article lead text', $html);
        $this->assertStringContainsString('Jane Author', $html);
    }

    public function testShowEndpointThrowsNotFoundForInvalidSlug(): void
    {
        $this->mockWordpressApi->method('getPageByUrl')->willThrowException(new NotFoundException('not found'));

        $this->client->request('GET', '/news/wrong-slug');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testShowEndpointHandlesNonSuccessAcfResponseGracefully(): void
    {
        $mockPage = $this->createMockPage([
            'post_lead_text' => 'The article lead text',
            'content' => '<p>The article body</p>',
        ], 'my-article', 42);

        $this->mockWordpressApi->method('getPageByUrl')->willReturn($mockPage);

        $this->mockHttpClient->setResponseFactory(function () {
            return new MockResponse('', ['http_code' => 404]);
        });

        $this->client->request('GET', '/news/my-article');

        $this->assertResponseIsSuccessful();
        $html = $this->client->getResponse()->getContent();

        $this->assertStringContainsString('The article lead text', $html);
    }

    public function testShowEndpointBuildsContentGroupWhenSectorsPresent(): void
    {
        $mockPage = $this->createMockPage([
            'post_lead_text' => 'The article lead text',
            'content' => '<p>The article body</p>',
            'sectors' => ['Health', 'Education'],
        ], 'my-article', 42);

        $this->mockWordpressApi->method('getPageByUrl')->willReturn($mockPage);

        $this->mockHttpClient->setResponseFactory(function () {
            return new MockResponse(json_encode(['acf' => []]));
        });

        $this->client->request('GET', '/news/my-article');

        $this->assertResponseIsSuccessful();
    }

    private function stubAllTerms(): void
    {
        $contentTypeTerms = new TermCollection();
        $contentTypeTerms->addItem(new Term(1, 'Whitepaper', 'whitepaper', '/whitepaper'));
        $contentTypeTerms->addItem(new Term(2, 'Webinar', 'webinar', '/webinar'));

        $this->mockWordpressApi->method('getAllTerms')->willReturnMap([
            ['content_type', $contentTypeTerms],
            ['categories', new TermCollection()],
            ['sectors', new TermCollection()],
            ['products_services', new TermCollection()],
        ]);
    }

    private function createMockPage(array $content, string $slug = 'article', int $id = 1): Page
    {
        $mockContent = CMSContentMockFactory::createMockContent($content);

        $mockPage = $this->createMock(Page::class);
        $mockPage->method('getContent')->willReturn($mockContent);
        $mockPage->method('getId')->willReturn($id);
        $mockPage->method('getUrlSlug')->willReturn($slug);
        $mockPage->method('getTitle')->willReturn(ucfirst(str_replace('-', ' ', $slug)));
        $categoryTerms = new TermCollection();
        $categoryTerms->addItem(new Term(1, 'General', 'general', '/general'));
        $mockPage->method('getTaxonomies')->willReturn(['categories' => $categoryTerms]);

        return $mockPage;
    }
}
