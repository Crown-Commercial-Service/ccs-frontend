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

class EventsControllerTest extends AbstractControllerTestCase
{
    private function stubAllTerms(int $audienceCount = 3, int $eventTypeCount = 2, int $psCount = 2, int $sectorsCount = 2): void
    {
        $makeTerms = function (int $count): TermCollection {
            $terms = new TermCollection();
            for ($i = 1; $i <= $count; $i++) {
                $terms->addItem(new Term($i, "Term {$i}", "term-{$i}", "/term-{$i}"));
            }
            return $terms;
        };

        $this->mockWordpressApi->method('getAllTerms')->willReturnMap([
            ['audience_tag', $makeTerms($audienceCount)],
            ['event_type', $makeTerms($eventTypeCount)],
            ['products_services', $makeTerms($psCount)],
            ['sectors', $makeTerms($sectorsCount)],
        ]);
    }

    private function emptyPageCollection(): PageCollection
    {
        $pagination = new Pagination();
        $pagination->setTotalResults(0);
        $pagination->setTotalPages(1);

        return new PageCollection($pagination);
    }

    // --- list() ---

    public function testListEndpointRendersSuccessfully(): void
    {
        $this->stubAllTerms();
        $this->mockWordpressApi->method('listPages')->willReturn($this->emptyPageCollection());

        $this->client->request('GET', '/events/1');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('<h1 class="govuk-heading-xl page-title page-title--tight">Events</h1>', $this->client->getResponse()->getContent());
    }

    public function testListEndpointThrowsNotFoundWhenListPagesThrowsNotFoundException(): void
    {
        $this->stubAllTerms();
        $this->mockWordpressApi->method('listPages')->willThrowException(new NotFoundException('not found'));

        $this->client->request('GET', '/events/1');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testListEndpointThrowsNotFoundWhenListPagesThrowsPaginationException(): void
    {
        $this->stubAllTerms();
        $this->mockWordpressApi->method('listPages')->willThrowException(new PaginationException('bad page'));

        $this->client->request('GET', '/events/1');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testListEndpointBuildsFilterOptionsFromQueryParams(): void
    {
        $this->stubAllTerms(audienceCount: 3, eventTypeCount: 2);

        $this->mockWordpressApi->expects($this->once())
            ->method('listPages')
            ->with(
                1,
                $this->callback(function (array $options) {
                    return $options['audience_tag'] === '1,2' && $options['event_type'] === null;
                })
            )
            ->willReturn($this->emptyPageCollection());

        $this->client->request('GET', '/events/1?audience_tag[]=1&audience_tag[]=2&event_type[]=1&event_type[]=2');

        $this->assertResponseIsSuccessful();
    }

    public function testListEndpointNullsFiltersWhenViewAllFlagsPresent(): void
    {
        $this->stubAllTerms();

        $this->mockWordpressApi->expects($this->once())
            ->method('listPages')
            ->with(
                1,
                $this->callback(function (array $options) {
                    return $options['audience_tag'] === null
                        && $options['event_type'] === null
                        && $options['products_services'] === null
                        && $options['sectors'] === null;
                })
            )
            ->willReturn($this->emptyPageCollection());

        $this->client->request('GET', '/events/1?audience_tag[]=1&allAudience=1&allType=1&allPS=1&allSectors=1');

        $this->assertResponseIsSuccessful();
    }

    public function testListEndpointUsesPageFromRouteParameter(): void
    {
        $this->stubAllTerms();

        $this->mockWordpressApi->expects($this->once())
            ->method('listPages')
            ->with(3, $this->anything())
            ->willReturn($this->emptyPageCollection());

        $this->client->request('GET', '/events/3');

        $this->assertResponseIsSuccessful();
    }

    public function testListEndpointDefaultsToPageOneWhenPageQueryParamNonNumeric(): void
    {
        $this->stubAllTerms();

        $this->mockWordpressApi->expects($this->once())
            ->method('listPages')
            ->with(1, $this->anything())
            ->willReturn($this->emptyPageCollection());

        $this->client->request('GET', '/events/1?page=abc');

        $this->assertResponseIsSuccessful();
    }

    // --- show() ---

    private function createMockEvent(array $content, string $title = 'Some Event'): Page
    {
        $mockContent = CMSContentMockFactory::createMockContent($content);

        $mockPage = $this->createMock(Page::class);
        $mockPage->method('getContent')->willReturn($mockContent);
        $mockPage->method('getTitle')->willReturn($title);
        $mockPage->method('getFeaturedImage')->willReturn(null);

        return $mockPage;
    }

    private function baseEventContent(string $locationType): array
    {
        return [
            'location_type'  => $locationType,
            'start_datetime' => new \DateTime('2026-08-01 09:00:00'),
            'end_datetime'   => new \DateTime('2026-08-01 17:00:00'),
            'description'    => '<p>Event details</p>',
            'cta_destination' => 'https://example.com/register',
        ];
    }

    public function testShowEndpointRendersInPersonEventSuccessfully(): void
    {
        $content = array_merge($this->baseEventContent('In Person'), [
            'place_name'       => 'Main Hall',
            'street_address'   => '1 High Street',
            'address_locality' => 'London',
            'postal_code'      => 'AB1 2CD',
            'address_region'   => 'Greater London',
            'address_country'  => 'UK',
        ]);

        $this->mockWordpressApi->method('getPage')->willReturn($this->createMockEvent($content));

        $this->client->request('GET', '/events/42/some-event');

        $this->assertResponseIsSuccessful();
        $html = $this->client->getResponse()->getContent();

        $this->assertStringContainsString('https:\/\/schema.org\/OfflineEventAttendanceMode', $html);
        $this->assertStringContainsString('Main Hall', $html);
    }

    public function testShowEndpointRendersOnlineEventSuccessfully(): void
    {
        $content = $this->baseEventContent('Online');

        $this->mockWordpressApi->method('getPage')->willReturn($this->createMockEvent($content));

        $this->client->request('GET', '/events/42/some-event');

        $this->assertResponseIsSuccessful();
        $html = $this->client->getResponse()->getContent();

        $this->assertStringContainsString('https:\/\/schema.org\/OnlineEventAttendanceMode', $html);
        $this->assertStringContainsString('https:\/\/example.com\/register', $html);
    }

    public function testShowEndpointRendersMixedModeEventSuccessfully(): void
    {
        $content = array_merge($this->baseEventContent('Online and In Person'), [
            'place_name'       => 'Main Hall',
            'street_address'   => '1 High Street',
            'address_locality' => 'London',
            'postal_code'      => 'AB1 2CD',
            'address_region'   => 'Greater London',
            'address_country'  => 'UK',
        ]);

        $this->mockWordpressApi->method('getPage')->willReturn($this->createMockEvent($content));

        $this->client->request('GET', '/events/42/some-event');

        $this->assertResponseIsSuccessful();
        $html = $this->client->getResponse()->getContent();

        $this->assertStringContainsString('https:\/\/schema.org\/MixedEventAttendanceMode', $html);
        $this->assertStringContainsString('VirtualLocation', $html);
        $this->assertStringContainsString('Main Hall', $html);
    }

    public function testShowEndpointThrowsNotFoundWhenGetPageThrowsNotFoundException(): void
    {
        $this->mockWordpressApi->method('getPage')->willThrowException(new NotFoundException('not found'));

        $this->client->request('GET', '/events/42/some-event');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testShowEndpointRendersErrorTemplateWhenGetPageThrowsError(): void
    {
        $this->mockWordpressApi->method('getPage')->willThrowException(new \Error('boom'));

        $this->client->request('GET', '/events/42/some-event');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Sorry, this event has already taken place', $this->client->getResponse()->getContent());
    }

    public function testShowEndpointBuildsContentGroupWhenSectorsPresent(): void
    {
        $content = array_merge($this->baseEventContent('Online'), [
            'sectors' => ['Health', 'Education'],
        ]);

        $this->mockWordpressApi->method('getPage')->willReturn($this->createMockEvent($content));

        $this->client->request('GET', '/events/42/some-event');

        $this->assertResponseIsSuccessful();
    }
}
