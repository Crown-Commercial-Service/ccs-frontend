<?php

declare(strict_types=1);

namespace App\Tests\App\Controller;

class EnergyControllerTest extends AbstractControllerTestCase
{
    protected function tearDown(): void
    {
        unset($_REQUEST['history']);

        parent::tearDown();
    }

    private function seedHistory(array $history): void
    {
        $_REQUEST['history'] = json_encode($history);
    }

    public function testStartRendersFirstQuestionOnFreshGetRequest(): void
    {
        $this->client->request('GET', '/find-an-energy-solution/question');

        $this->assertResponseIsSuccessful();
        $html = $this->client->getResponse()->getContent();

        $this->assertStringContainsString('Would you like a short or long term flexible trading agreement?', $html);
        $this->assertStringContainsString('Between 18 and 24 months', $html);
        $this->assertStringContainsString('Over 24 months', $html);
    }

    public function testStartRendersErrorSummaryWhenAnswerMissingOnPost(): void
    {
        $this->seedHistory([
            ['questionID' => '0', 'selectedAnswer' => null],
        ]);

        $this->client->request('POST', '/find-an-energy-solution/question');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('You will need to select one option', $this->client->getResponse()->getContent());
    }

    public function testStartRedirectsToNextQuestionForNonTerminalDecision(): void
    {
        $this->seedHistory([
            ['questionID' => '0', 'selectedAnswer' => null],
        ]);

        $this->client->request('POST', '/find-an-energy-solution/question', ['answer' => '0']);

        $this->assertResponseRedirects();
        $location = $this->client->getResponse()->headers->get('Location');
        $this->assertStringContainsString('/find-an-energy-solution/question', $location);

        $query = [];
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
        $redirectedHistory = json_decode((string) $query['history'], true);

        $this->assertSame('0', $redirectedHistory[0]['questionID']);
        $this->assertSame('0', $redirectedHistory[0]['selectedAnswer']);
        $this->assertSame(1, $redirectedHistory[1]['questionID']);
        $this->assertNull($redirectedHistory[1]['selectedAnswer']);
    }

    public function testStartRedirectsToResultForTerminalDecision(): void
    {
        $this->seedHistory([
            ['questionID' => '0', 'selectedAnswer' => null],
        ]);

        $this->client->request('POST', '/find-an-energy-solution/question', ['answer' => '1']);

        $this->assertResponseRedirects();
        $location = $this->client->getResponse()->headers->get('Location');

        $this->assertStringContainsString('/find-an-energy-solution/energy_result', $location);
        $this->assertStringContainsString('recommendation=V30', $location);
    }

    public function testResultPageRendersRecommendationAndAnswerHistory(): void
    {
        $historyParam = http_build_query([
            'history' => [
                ['questionID' => 0, 'selectedAnswer' => 0],
            ],
        ]);

        $this->client->request('GET', '/find-an-energy-solution/energy_result?' . $historyParam . '&recommendation=V30');

        $this->assertResponseIsSuccessful();
        $html = $this->client->getResponse()->getContent();

        $this->assertStringContainsString('Long term variable', $html);
        $this->assertStringContainsString('Would you like a short or long term flexible trading agreement?', $html);
        $this->assertStringContainsString('Between 18 and 24 months', $html);
    }

    public function testResultPageRendersFallbackForUnknownRecommendation(): void
    {
        $historyParam = http_build_query([
            'history' => [
                ['questionID' => 0, 'selectedAnswer' => 0],
            ],
        ]);

        $this->client->request('GET', '/find-an-energy-solution/energy_result?' . $historyParam . '&recommendation=UNKNOWN_CODE');

        $this->assertResponseIsSuccessful();
        $html = $this->client->getResponse()->getContent();

        $this->assertStringContainsString('Something went wrong', $html);
    }
}
