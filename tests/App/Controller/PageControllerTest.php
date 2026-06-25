<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Strata\Frontend\Cms\Wordpress;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpClient\TraceableHttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PageControllerTest extends WebTestCase
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
        $client = static::createClient();

        $fixturePath = __DIR__ . '/../../Fixtures/homepage_components.json';
        $mockJson = file_get_contents($fixturePath);
        $mockResponse = new MockResponse($mockJson, ['http_code' => 200]);
        $mockHttpClient = new MockHttpClient($mockResponse);

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

        $mockWordpress = $this->createMock(Wordpress::class);
        $mockWordpress->method('listPages')->willReturn($realCollection);
        $mockWordpress->method('setContentType')
            ->with($this->callback(function (string $contentType): bool {
                return in_array($contentType, ['news', 'page'], true);
            }));

        // Inject mocks into the test container wrapper
        $traceableHttpClient = new TraceableHttpClient($mockHttpClient);
        static::getContainer()->set(HttpClientInterface::class, $traceableHttpClient);
        static::getContainer()->set(Wordpress::class, $mockWordpress);

        // Execute the request
        $crawler = $client->request('GET', '/');

        // Assertions
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Transforming Public Procurement');
        $this->assertSelectorTextContains('body', 'How to supply');
        $this->assertSelectorTextContains('body', 'How to buy');
        $this->assertSelectorTextContains('body', 'Mocked News Article');

    }
    public function testGenericPageTemplateLoadsWithMockedData(): void
        {
            $client = static::createClient();

            // 1. Read and decode your raw WordPress JSON fixture file
            $pageFixturePath = __DIR__ . '/../../Fixtures/technology_page.json';
            $pageJson = file_get_contents($pageFixturePath);
            $pageData = json_decode($pageJson, true);

            // 2. Read your Option Cards JSON fixture file
            $cardsFixturePath = __DIR__ . '/../../Fixtures/option_cards.json';
            $cardsJson = file_get_contents($cardsFixturePath);

            // 3. Mock the HTTP Client purely for the Option Cards endpoint call
            $mockHttpClient = new MockHttpClient(new MockResponse($cardsJson, ['http_code' => 200]));
            $traceableHttpClient = new TraceableHttpClient($mockHttpClient);
            static::getContainer()->set(HttpClientInterface::class, $traceableHttpClient);

            // 4. Use the clean private helper function to generate our data-fed Content object
            $mockContent = $this->createMockContent($pageData['acf'] ?? []);

            // 5. Mock the Page entity using strings directly from your JSON array
            $mockPage = $this->createMock(\Strata\Frontend\Content\Page::class);
            $mockPage->method('getTitle')->willReturn($pageData['title']['rendered'] ?? 'Fallback Title');
            $mockPage->method('getTemplate')->willReturn($pageData['template'] ?? '');
            $mockPage->method('getContent')->willReturn($mockContent);     


            // 6. Mock the Wordpress Service and inject it
            $mockWordpress = $this->createMock(Wordpress::class);
            $mockWordpress->method('setContentType')->with('page');
            $mockWordpress->method('getPageByUrl')->willReturn($mockPage);
            static::getContainer()->set(Wordpress::class, $mockWordpress);

            // 7. Execute the route matching your JSON slug
            $_SERVER['QUERY_STRING'] = '';
            $client->request('GET', '/products-and-services/technology');

            // 8. Assertions: Powered completely by your true template layout elements!
            $this->assertResponseIsSuccessful();
            
            // Verifies the core titles match
            $this->assertSelectorTextContains('.page-title', 'Technology');
            
            // Verifies the contact form header text from the JSON prints perfectly into the form markup
            $this->assertSelectorTextContains('.ccs-inline-contact-form h2', 'Speak to an expert about technology agreements');
            
            // Verifies the input hidden campaign code field matches your JSON state ("TECH")
            $this->assertSelectorExists('input[name="subject"][value="TECH"]');
        }

    /**
     * Private helper blueprint that satisfies both PHP method signatures 
     * and complex Twig dot-notations (.value / ->byName()) perfectly.
     */
    private function createMockContent(array $acfData): \Strata\Frontend\Content\Field\ContentFieldCollection
    {
        return new #[\AllowDynamicProperties] class($acfData) extends \Strata\Frontend\Content\Field\ContentFieldCollection {
            private array $data;
            
            public function __construct(array $data) 
            {
                // Map keys to real properties so property_exists() works for resources lists
                foreach ($data as $key => $value) {
                    $this->$key = $value;
                }
                $this->data = $data;
            }
            
            /**
             * MATCH PARENT SIGNATURE: Must return ?ContentFieldInterface
             */
            public function get($name): ?\Strata\Frontend\Content\Field\ContentFieldInterface
            { 
                return $this->offsetGet($name);
            }
            
            /**
             * Twig Object Access: Handles dot-notation properties like page.content.hero_heading
             */
            public function __get(string $key)
            {
                if (!array_key_exists($key, $this->data)) {
                    return null;
                }

                $rawValue = $this->data[$key];
                if ($rawValue === false || $rawValue === null || $rawValue === '') {
                    return $rawValue;
                }

                return $this->wrapValue($rawValue);
            }
            
            // Satisfies ArrayAccess rules
            public function offsetExists($index): bool { return array_key_exists($index, $this->data); }
            
            /**
             * MATCH PARENT SIGNATURE: Must return ContentFieldInterface exactly (non-nullable)
             */
            public function offsetGet($index): \Strata\Frontend\Content\Field\ContentFieldInterface
            {
                return $this->wrapValue($this->data[$index] ?? null);
            }

            /**
             * DYNAMIC VALUE WRAPPER: Ensures every content key resolves into a
             * wrapper object that fulfills ContentFieldInterface for array access.
             */
            private function wrapValue($rawValue): \Strata\Frontend\Content\Field\ContentFieldInterface
            {
                return new class($rawValue) implements \Strata\Frontend\Content\Field\ContentFieldInterface {
                    private $val;
                    public function __construct($v) { $this->val = $v; }

                    // Handles property checks like image.alt and .value accesses
                    public function __get($prop)
                    {
                        if ($prop === 'value') {
                            return $this->val;
                        }

                        if (is_array($this->val)) {
                            return $this->val[$prop] ?? null;
                        }

                        return null;
                    }

                    // Handles specialized object calls like image.byName('large')
                    public function __call($method, $args)
                    {
                        if ($method === 'byName' && is_array($this->val)) {
                            return $this->val['sizes'][$args[0]] ?? null;
                        }
                        return $this->val;
                    }

                    // Satisfies the 6 mandatory abstract methods required by ContentFieldInterface
                    public function getName(): string { return ''; }
                    public function getType(): string { return ''; }
                    public function getValue() { return $this->val; }
                    public function hasHtml(): bool { return false; }
                    public function setName(string $name): \Strata\Frontend\Content\Field\ContentFieldInterface { return $this; }
                    public function __toString(): string { return is_scalar($this->val) ? (string) $this->val : ''; }
                };
            }
            
            public function offsetSet($offset, $value): void {}
            public function offsetUnset($offset): void {}
        };
    }
   
}

    