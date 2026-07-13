<?php

declare(strict_types=1);

namespace App\Tests\App\Controller;

use App\Controller\FormController;
use App\Tests\App\Mock\CMSContentMockFactory;
use Strata\Frontend\Content\Page;
use Strata\Frontend\Exception\NotFoundException;

class WhitepaperControllerTest extends AbstractControllerTestCase
{
    private $mockFormController;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockFormController = $this->createMock(FormController::class);
        static::getContainer()->set(FormController::class, $this->mockFormController);
    }

    private function createMockWhitepaper(int $id = 42, string $slug = 'some-slug'): Page
    {
        $mockContent = CMSContentMockFactory::createMockContent([
            'campaign_code' => 'CAMP123',
            'description'   => 'A great whitepaper',
        ]);

        $mockPage = $this->createMock(Page::class);
        $mockPage->method('getId')->willReturn($id);
        $mockPage->method('getUrlSlug')->willReturn($slug);
        $mockPage->method('getTitle')->willReturn('My Whitepaper');
        $mockPage->method('getContent')->willReturn($mockContent);

        return $mockPage;
    }

    // --- request() ---

    public function testRequestGetRendersFormSuccessfully(): void
    {
        $mockPage = $this->createMockWhitepaper();

        $this->mockWordpressApi->method('getPageByUrl')
            ->with('/news/whitepapers/some-slug')
            ->willReturn($mockPage);

        $this->client->request('GET', '/whitepaper/request/42/some-slug');

        $this->assertResponseIsSuccessful();
        $html = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('My Whitepaper', $html);
        $this->assertStringNotContainsString('There is a problem', $html);
    }

    public function testRequestReturns404WhenResourceNotFound(): void
    {
        $this->mockWordpressApi->method('getPageByUrl')
            ->willThrowException(new NotFoundException('not found'));

        $this->client->request('GET', '/whitepaper/request/42/missing-slug');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testRequestPostWithValidDataRedirectsToConfirmation(): void
    {
        $mockPage = $this->createMockWhitepaper(42, 'some-slug');

        $this->mockWordpressApi->method('getPageByUrl')->willReturn($mockPage);
        $this->mockFormController->method('sendToSalesforceForDownload')->willReturn(false);

        $this->client->request('POST', '/whitepaper/request/42/some-slug', [
            'name'     => 'Jane Doe',
            'email'    => 'jane@example.com',
            'company'  => 'Acme Ltd',
            'jobTitle' => 'Buyer',
        ]);

        // appBaseUrl is bound to APP_BASE_URL="http://localhost/" (trailing slash) in the test env,
        // which combined with the controller's leading slash produces a double slash - this reflects
        // the controller's actual behaviour, not a test assumption.
        $this->assertResponseRedirects('http://localhost//whitepaper/confirmation/42/some-slug/?');
    }

    public function testRequestPostWithInvalidDataRendersErrors(): void
    {
        $mockPage = $this->createMockWhitepaper();

        $this->mockWordpressApi->method('getPageByUrl')->willReturn($mockPage);
        $this->mockFormController->method('sendToSalesforceForDownload')->willReturn([
            'emailErr' => ['errors' => ['Enter a valid email address'], 'link' => '#email'],
        ]);

        $this->client->request('POST', '/whitepaper/request/42/some-slug', [
            'name'     => 'Jane Doe',
            'email'    => 'not-an-email',
            'company'  => 'Acme Ltd',
            'jobTitle' => 'Buyer',
        ]);

        $this->assertResponseIsSuccessful();
        $html = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('There is a problem', $html);
        $this->assertStringContainsString('Enter a valid email address', $html);
    }

    public function testRequestPostWithHoneypotFilledIsRejected(): void
    {
        $mockPage = $this->createMockWhitepaper();

        $this->mockWordpressApi->method('getPageByUrl')->willReturn($mockPage);

        $this->client->request('POST', '/whitepaper/request/42/some-slug', [
            'name'    => 'Jane Doe',
            'email'   => 'jane@example.com',
            'surname' => '1',
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    // --- show() ---

    public function testShowRendersConfirmationSuccessfully(): void
    {
        $mockPage = $this->createMockWhitepaper();

        $this->mockWordpressApi->method('getPageByUrl')
            ->with('/news/whitepapers/some-slug')
            ->willReturn($mockPage);

        $this->client->request('GET', '/whitepaper/confirmation/42/some-slug');

        $this->assertResponseIsSuccessful();
        $html = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('You can now download this whitepaper', $html);
    }

    public function testShowReturns404WhenResourceNotFound(): void
    {
        $this->mockWordpressApi->method('getPageByUrl')
            ->willThrowException(new NotFoundException('not found'));

        $this->client->request('GET', '/whitepaper/confirmation/42/missing-slug');

        $this->assertResponseStatusCodeSame(404);
    }
}
